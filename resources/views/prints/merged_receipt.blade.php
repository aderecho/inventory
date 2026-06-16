<body>
    @if ($groupedParItems->isNotEmpty())
        @include('prints.par_receipt', ['groupedParItems' => $groupedParItems])
    @endif

    @if ($icsItems->isNotEmpty())
        @include('prints.ics_receipt', ['acknowledgementItems' => $icsItems])
    @endif
</body>