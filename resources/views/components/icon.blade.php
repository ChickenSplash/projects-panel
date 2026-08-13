@props(['name'])

@php($svg = $attributes->merge([
    'class' => 'size-4',
    'viewBox' => '0 0 24 24',
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => '1.75',
    'stroke-linecap' => 'round',
    'stroke-linejoin' => 'round',
    'aria-hidden' => 'true',
]))

@switch($name)
    @case('sun')
        <svg {{ $svg }}>
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
        @break

    @case('moon')
        <svg {{ $svg }}>
            <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
        </svg>
        @break

    @case('auto')
        <svg {{ $svg }}>
            <circle cx="12" cy="12" r="9" />
            <path d="M12 3a9 9 0 0 1 0 18Z" fill="currentColor" stroke="none" />
        </svg>
        @break

    @case('sparkle')
        <svg {{ $svg }}>
            <path d="M12 3.5 13.8 9 19.5 10.8 13.8 12.6 12 18.1 10.2 12.6 4.5 10.8 10.2 9Z" />
            <path d="M18.5 15.5 19.2 17.6 21.3 18.3 19.2 19 18.5 21.1 17.8 19 15.7 18.3 17.8 17.6Z" />
        </svg>
        @break

    @case('trash')
        <svg {{ $svg }}>
            <path d="M4 7h16M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7m3 0-.7 11.1a2 2 0 0 1-2 1.9H9.2a2 2 0 0 1-2-1.9L6.5 7" />
        </svg>
        @break

    @case('link')
        <svg {{ $svg }}>
            <path d="M8 16 16 8m-6 0h6v6" />
        </svg>
        @break

    @case('chevron')
        <svg {{ $svg }}>
            <path d="m6 9.5 6 5.5 6-5.5" />
        </svg>
        @break

    @case('user')
        <svg {{ $svg }}>
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5 20a7 7 0 0 1 14 0" />
        </svg>
        @break

    @case('exit')
        <svg {{ $svg }}>
            <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M11 8.5 14.5 12 11 15.5M14.5 12H4" />
        </svg>
        @break
@endswitch
