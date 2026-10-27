<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Angebot {{ $offer->number }}</title>
    <style>
        @page { size: A4; margin: 16mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #17202a; font-size: 11px; margin: 0; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #15233d; padding-bottom: 12px; }
        .word { font-size: 28px; font-weight: 900; letter-spacing: .05em; color: #15233d; }
        .company { text-align: right; line-height: 1.45; color: #475467; }
        .title { font-size: 25px; font-weight: 900; margin: 28px 0 14px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 22px; }
        .cap { font-size: 9px; text-transform: uppercase; font-weight: 700; color: #667085; margin-bottom: 5px; }
        .customer { line-height: 1.55; }
        .meta td { padding: 3px 0; } .meta td:last-child { text-align: right; font-weight: 700; } .meta { width: 100%; border-collapse: collapse; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { background: #15233d; color: #fff; padding: 8px 7px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.items td { border-bottom: 1px solid #dde3eb; padding: 9px 7px; vertical-align: top; }
        .r { text-align: right !important; } .sub { font-size: 9px; color: #667085; margin-top: 2px; }
        .sum { margin: 18px 0 0 auto; width: 310px; } .sum div { display: flex; justify-content: space-between; padding: 5px 0; }
        .sum .total { font-size: 16px; font-weight: 900; border-top: 2px solid #15233d; margin-top: 5px; padding-top: 9px; }
        .note { margin-top: 25px; padding: 12px; border: 1px solid #dfe5ee; border-radius: 8px; line-height: 1.5; white-space: pre-line; }
        .valid { margin-top: 16px; font-weight: 700; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; border-top: 1px solid #dfe5ee; padding-top: 7px; color: #667085; font-size: 8.5px; display: flex; justify-content: space-between; }
        .no-print { text-align: right; margin-bottom: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @php
        $eur = fn (float $value): string => number_format($value, 2, ',', '.').' €';
        $customer = array_filter([$offer->company, $offer->contact, $offer->street, $offer->city, $offer->email]);
    @endphp
    <div class="no-print"><button onclick="window.print()">Drucken / als PDF speichern</button></div>
    <div class="head">
        <div class="word">LOOXIS.de</div>
        <div class="company"><b>LOOXIS GmbH</b><br>Magdeburger Str. 11<br>32423 Minden<br>hello@looxis.com · 0571-5904000</div>
    </div>
    <div class="title">Angebot</div>
    <div class="two">
        <div><div class="cap">Angebot für</div><div class="customer">{!! $customer === [] ? '–' : implode('<br>', array_map('e', $customer)) !!}</div></div>
        <div>
            <table class="meta">
                <tr><td>Angebotsnummer</td><td>{{ $offer->number }}</td></tr>
                <tr><td>Datum</td><td>{{ $offer->created_on->format('d.m.Y') }}</td></tr>
                <tr><td>Gültig bis</td><td>{{ $offer->valid_until->format('d.m.Y') }}</td></tr>
            </table>
        </div>
    </div>
    <table class="items">
        <thead><tr><th>Pos.</th><th>Artikel</th><th class="r">Menge</th><th class="r">Stück netto</th><th class="r">Rabatt</th><th class="r">Gesamt netto</th></tr></thead>
        <tbody>
            @foreach ($offer->items as $index => $item)
                @php($vat = ((float) $item['vat']) / 100)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><b>{{ $item['name'] }}</b><div class="sub">{{ $item['sku'] }}</div></td>
                    <td class="r">{{ $item['qty'] }}</td>
                    <td class="r">{{ $eur($item['unit_gross'] / (1 + $vat)) }}</td>
                    <td class="r">{{ number_format((float) $item['discount_pct'], 1, ',', '.') }} %</td>
                    <td class="r"><b>{{ $eur($item['unit_gross'] * $item['qty'] / (1 + $vat)) }}</b></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="sum">
        <div><span>Summe netto</span><b>{{ $eur($totals['net']) }}</b></div>
        <div><span>MwSt.</span><b>{{ $eur($totals['vat']) }}</b></div>
        <div class="total"><span>Gesamt brutto</span><span>{{ $eur($totals['gross']) }}</span></div>
    </div>
    @if ($offer->note)<div class="note"><div class="cap">Bemerkung</div>{{ $offer->note }}</div>@endif
    <div class="valid">Dieses Angebot ist gültig bis einschließlich {{ $offer->valid_until->format('d.m.Y') }}.</div>
    <div class="footer"><span>LOOXIS GmbH · Magdeburger Str. 11 · 32423 Minden</span><span>{{ $offer->number }}</span></div>
</body>
</html>
