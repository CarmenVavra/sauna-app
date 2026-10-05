<x-app-layout title="Jahresbilanz">
    <x-kicker style="margin-bottom:56px">Jahresbilanz {{ $report['year'] }} · bis {{ $report['until'] }}</x-kicker>

    <div style="display:flex;flex-wrap:wrap;gap:32px 64px;align-items:flex-end;margin-bottom:72px">
        <x-plate-number :value="$report['count']" size="xl" aria-hidden="true" />
        <div style="padding-bottom:8px;flex:1 1 360px">
            <h1 style="font-size:clamp(30px,5vw,46px);line-height:1.1;margin:0 0 16px;max-width:20ch">
                <span class="sr-only">{{ $report['count'] }}</span> Saunagänge, {{ $report['cost'] }} für Münzen.
            </h1>
            <p style="font-size:18px;margin:0;max-width:52ch">{{ $report['summary'] }}</p>
        </div>
    </div>

    <div class="stats" style="max-width:none;margin-bottom:80px">
        <x-stat :value="$report['coins']" label="Münzen verbraucht" small />
        <x-stat :value="$report['hours']" label="Stunden Heizzeit" small />
        <x-stat :value="$report['avgTemp']" :label="'Ø Temperatur · max. '.$report['maxTemp']" small />
        <x-stat :value="$report['infusions']" label="Aufgüsse" small />
        <x-stat :value="$report['favourite']" label="Lieblingssauna" small />
    </div>

    <div class="two-col-narrow">
        <section aria-labelledby="months-title">
            <h2 class="h3" id="months-title">Nach Monat</h2>
            <div class="bars" aria-hidden="true">
                @foreach ($report['months'] as $m)
                    <div class="bar-col">
                        <span class="small">{{ $m['n'] ?: '' }}</span>
                        <div class="bar" style="height:{{ $m['height'] }}px"></div>
                    </div>
                @endforeach
            </div>
            <div class="bar-labels" aria-hidden="true">
                @foreach ($report['months'] as $m)
                    <div>
                        <div class="small semibold">{{ $m['label'] }}</div>
                        <div class="small muted">{{ $m['cost'] }}</div>
                    </div>
                @endforeach
            </div>
            <ul class="sr-only">
                @foreach ($report['months'] as $m)
                    <li>{{ $m['name'] }}: {{ $m['n'] }} Saunagänge, Kosten {{ $m['cost'] }}</li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="oils-title">
            <h2 class="h3" id="oils-title">Öle im Aufguss</h2>
            <p class="text-14 muted">{{ $report['doseLine'] }}</p>
            <ul class="list">
                @foreach ($report['oils'] as $o)
                    <li style="padding:9px 0;border-bottom:1px solid var(--color-line)">
                        <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:6px;gap:12px">
                            <span class="semibold">{{ $o['name'] }}</span><span>{{ $o['label'] }}</span>
                        </div>
                        <div class="oil-bar" style="width:{{ $o['width'] }}%" aria-hidden="true"></div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-app-layout>
