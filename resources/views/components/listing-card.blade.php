@props(['listing'])

<a href="{{ route('catalog.show', $listing->slug) }}"
   class="group flex flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-card transition hover:-translate-y-0.5 hover:border-leaf-300 hover:shadow-card-hover">
    <div class="relative aspect-[4/3] overflow-hidden bg-stone-100">
        @if($listing->media->isNotEmpty())
            <img src="{{ $listing->media->first()->url() }}" alt="{{ $listing->name() }}"
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center text-stone-300">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-10 w-10">
                    <path d="M4 12c0-4.5 3.5-8 8-8s8 3.5 8 8M4 12c0 1 .5 2 1.5 2h13c1 0 1.5-1 1.5-2M4 12h16"
                          stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
            </div>
        @endif

        @if($listing->greenAttributes->isNotEmpty())
            <div class="absolute inset-x-2 top-2 flex flex-wrap gap-1">
                @foreach($listing->greenAttributes->take(2) as $attribute)
                    <span class="rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-semibold text-leaf-700 shadow-sm ring-1 ring-leaf-100">
                        {{ $attribute->name() }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-2 p-4">
        <p class="line-clamp-2 text-sm font-semibold leading-snug text-leaf-900 group-hover:text-leaf-700">
            {{ $listing->name() }}
        </p>

        <p class="text-base font-bold text-leaf-800">{{ $listing->formattedPrice() }}</p>

        {{-- FR-102 — the buyer always knows who they are buying from. --}}
        <div class="mt-auto flex items-center gap-1.5 pt-1 text-xs text-stone-500">
            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-leaf-100 text-[10px] font-bold uppercase text-leaf-700">
                {{ Illuminate\Support\Str::substr($listing->sellerLabel(), 0, 1) }}
            </span>
            <span class="truncate">{{ $listing->sellerLabel() }}</span>
        </div>
    </div>
</a>
