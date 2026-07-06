<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";

/* ---------------- Live Clock (Asia/Manila) ---------------- */
const currentTime = ref(new Date());
let clockTimer = null;

onMounted(() => {
    clockTimer = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(clockTimer);
    clearInterval(weatherTimer);
});

const formattedTime = computed(() =>
    currentTime.value.toLocaleTimeString("en-PH", {
        timeZone: "Asia/Manila",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: true,
    })
);

const formattedDate = computed(() =>
    currentTime.value.toLocaleDateString("en-PH", {
        timeZone: "Asia/Manila",
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric",
    })
);

// YYYY-MM-DD in Asia/Manila, used to match against holiday API dates
const manilaDateKey = computed(() =>
    currentTime.value.toLocaleDateString("en-CA", { timeZone: "Asia/Manila" })
);

/* ---------------- Weather (Cebu City, PH) ---------------- */
const CEBU_LAT = 10.3157;
const CEBU_LON = 123.8854;

const weather = ref(null);
const isWeatherLoading = ref(true);
const weatherError = ref(false);
let weatherTimer = null;

const weatherCodeMap = {
    0: { label: "Clear Sky", icon: "fa-sun" },
    1: { label: "Mostly Clear", icon: "fa-sun" },
    2: { label: "Partly Cloudy", icon: "fa-cloud-sun" },
    3: { label: "Overcast", icon: "fa-cloud" },
    45: { label: "Foggy", icon: "fa-smog" },
    48: { label: "Foggy", icon: "fa-smog" },
    51: { label: "Light Drizzle", icon: "fa-cloud-rain" },
    53: { label: "Drizzle", icon: "fa-cloud-rain" },
    55: { label: "Dense Drizzle", icon: "fa-cloud-rain" },
    61: { label: "Light Rain", icon: "fa-cloud-showers-heavy" },
    63: { label: "Rain", icon: "fa-cloud-showers-heavy" },
    65: { label: "Heavy Rain", icon: "fa-cloud-showers-heavy" },
    80: { label: "Rain Showers", icon: "fa-cloud-showers-heavy" },
    81: { label: "Rain Showers", icon: "fa-cloud-showers-heavy" },
    82: { label: "Violent Showers", icon: "fa-cloud-showers-heavy" },
    95: { label: "Thunderstorm", icon: "fa-bolt" },
    96: { label: "Thunderstorm", icon: "fa-bolt" },
    99: { label: "Thunderstorm", icon: "fa-bolt" },
};

const weatherInfo = computed(() => {
    if (!weather.value) return { label: "—", icon: "fa-cloud" };
    return weatherCodeMap[weather.value.weathercode] ?? { label: "—", icon: "fa-cloud" };
});

const fetchWeather = async () => {
    try {
        isWeatherLoading.value = true;
        weatherError.value = false;

        const response = await fetch(
            `https://api.open-meteo.com/v1/forecast?latitude=${CEBU_LAT}&longitude=${CEBU_LON}&current_weather=true&timezone=Asia%2FManila`
        );

        if (!response.ok) throw new Error("Weather request failed");

        const data = await response.json();
        weather.value = data.current_weather;
    } catch (error) {
        weatherError.value = true;
    } finally {
        isWeatherLoading.value = false;
    }
};

onMounted(() => {
    fetchWeather();
    weatherTimer = setInterval(fetchWeather, 10 * 60 * 1000);
});

/* ---------------- PH Holidays ---------------- */
const holidays = ref([]);

const fetchHolidays = async (year) => {
    try {
        const [thisYear, nextYear] = await Promise.all([
            fetch(`https://date.nager.at/api/v3/PublicHolidays/${year}/PH`).then((r) => r.json()),
            fetch(`https://date.nager.at/api/v3/PublicHolidays/${year + 1}/PH`).then((r) => r.json()),
        ]);
        holidays.value = [...thisYear, ...nextYear];
    } catch (error) {
        holidays.value = [];
    }
};

onMounted(() => {
    fetchHolidays(new Date().getFullYear());
});

const todayHoliday = computed(() =>
    holidays.value.find((h) => h.date === manilaDateKey.value) ?? null
);

const nextHoliday = computed(() => {
    const upcoming = holidays.value
        .filter((h) => h.date > manilaDateKey.value)
        .sort((a, b) => (a.date > b.date ? 1 : -1));

    if (!upcoming.length) return null;

    const days = Math.ceil(
        (new Date(upcoming[0].date) - new Date(manilaDateKey.value)) / (1000 * 60 * 60 * 24)
    );

    return { ...upcoming[0], daysUntil: days };
});
</script>

<template>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 font-['Poppins']">
        <!-- Date -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10.5px] font-semibold tracking-wider uppercase text-gray-400">
                    Today
                </p>
                <p class="mt-2 text-[15px] font-bold text-gray-900 leading-snug">
                    {{ formattedDate }}
                </p>
            </div>
            <span class="shrink-0 w-9 h-9 rounded-full bg-[#005740]/10 flex items-center justify-center">
                <i class="fa-solid fa-calendar-day text-[#005740] text-[14px]"></i>
            </span>
        </div>

        <!-- Time -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10.5px] font-semibold tracking-wider uppercase text-gray-400">
                    Manila Time
                </p>
                <p class="mt-2 text-[19px] font-bold text-gray-900 tabular-nums leading-snug">
                    {{ formattedTime }}
                </p>
            </div>
            <span class="shrink-0 w-9 h-9 rounded-full bg-[#005740]/10 flex items-center justify-center">
                <i class="fa-solid fa-clock text-[#005740] text-[14px]"></i>
            </span>
        </div>

        <!-- Weather -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10.5px] font-semibold tracking-wider uppercase text-gray-400">
                    Cebu City
                </p>

                <template v-if="isWeatherLoading">
                    <p class="mt-2 text-[13px] text-gray-400">Loading...</p>
                </template>
                <template v-else-if="weatherError">
                    <p class="mt-2 text-[13px] text-gray-400">Unavailable</p>
                </template>
                <template v-else>
                    <p class="mt-2 text-[19px] font-bold text-gray-900 leading-snug">
                        {{ Math.round(weather.temperature) }}°C
                    </p>
                    <p class="text-[11.5px] text-gray-400 mt-0.5">
                        {{ weatherInfo.label }}
                    </p>
                </template>
            </div>
            <span class="shrink-0 w-9 h-9 rounded-full bg-[#005740]/10 flex items-center justify-center">
                <i :class="['fa-solid', weatherInfo.icon, 'text-[#005740] text-[14px]']"></i>
            </span>
        </div>

        <!-- Holiday -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10.5px] font-semibold tracking-wider uppercase text-gray-400">
                    {{ todayHoliday ? "Holiday Today" : "Next Holiday" }}
                </p>

                <template v-if="todayHoliday">
                    <p class="mt-2 text-[13px] font-bold text-[#850038] leading-snug">
                        {{ todayHoliday.localName }}
                    </p>
                </template>
                <template v-else-if="nextHoliday">
                    <p class="mt-2 text-[13px] font-bold text-gray-900 leading-snug">
                        {{ nextHoliday.localName }}
                    </p>
                    <p class="text-[11.5px] text-gray-400 mt-0.5">
                        in {{ nextHoliday.daysUntil }} day{{ nextHoliday.daysUntil === 1 ? "" : "s" }}
                    </p>
                </template>
                <template v-else>
                    <p class="mt-2 text-[13px] text-gray-400">—</p>
                </template>
            </div>
            <span class="shrink-0 w-9 h-9 rounded-full bg-[#850038]/10 flex items-center justify-center">
                <i class="fa-solid fa-star text-[#850038] text-[14px]"></i>
            </span>
        </div>
    </div>
</template>