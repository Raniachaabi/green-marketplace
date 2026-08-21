@props(['order', 'shipment'])

@php($steps = $shipment->trackerSteps($order))

@if(in_array($shipment->status, [\App\Enums\ShipmentStatus::Failed, \App\Enums\ShipmentStatus::Returned], true))
    <x-status-badge :status="$shipment->status" />
@elseif(! empty($steps))
    <ol class="space-y-0">
        @foreach($steps as $i => $step)
            <li class="flex gap-3">
                <div class="flex flex-col items-center">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold
                                 {{ $step['done']
                                        ? 'bg-leaf-600 text-white dark:bg-primary'
                                        : 'border-2 border-stone-300 text-transparent dark:border-border' }}">
                        @if($step['done'])
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-3 w-3">
                                <path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        @endif
                    </span>
                    @if(! $loop->last)
                        <span class="h-6 w-0.5 {{ $step['done'] ? 'bg-leaf-600 dark:bg-primary' : 'bg-stone-200 dark:bg-border' }}"></span>
                    @endif
                </div>
                <div class="pb-6 text-sm">
                    <p class="{{ $step['done'] ? 'font-semibold text-leaf-900 dark:text-foreground' : 'text-stone-400 dark:text-muted-foreground' }}">
                        {{ $step['label'] }}
                    </p>
                    @if($step['timestamp'])
                        <p class="text-xs text-stone-400 dark:text-muted-foreground/70">{{ $step['timestamp']->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
