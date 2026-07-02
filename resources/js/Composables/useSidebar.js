import { ref } from "vue";

const isSidebarOpen = ref(true);
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

export function useSidebar() {
    return { isSidebarOpen, toggleSidebar };
}