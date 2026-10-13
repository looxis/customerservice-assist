<?php

/*
| The live check set of the router (PROJ-37). Only invented or anonymous requests; no customer data.
| `runs` > 1 repeats a case (the Eisberg case must hold 5 of 5). `expected` must be in the final selection,
| `forbidden` must not; `no_additions` means the right answer of the router is "add nothing".
| `translation` (German text) is sent with the original in the "with translation" variant of foreign cases.
*/

$eisberg = "leider ist das Motiv, welches eingelasert wurde, sehr klein und der Eisberg ist auch etwas anders als unser Muster.\nDas Bild sieht irgendwie sehr verloren darin aus.\nDas Motiv in unserem aktuellen Eisberg L (vom Kunden) ist sogar etwas kleiner als unser Musterbild im Eisberg M.\nBilder im Anhang - der Auftrag ist natürlich wieder einmal eilig";

return [
    'eisberg original' => ['group' => 'reseller', 'products' => ['3d-glass-photo'], 'text' => $eisberg, 'runs' => 5,
        'expected' => ['PRODUCT-006', 'PLAYBOOK-018', 'PLAYBOOK-019'], 'forbidden' => ['PLAYBOOK-008', 'PLAYBOOK-009', 'PLAYBOOK-014', 'POLICY-017', 'PLAYBOOK-003', 'PLAYBOOK-011']],
    'paraphrase: tiny photo, much empty space' => ['group' => 'private-looxis-de', 'products' => ['3d-glass-photo'], 'text' => 'Auf dem Glas wirkt mein Foto winzig, drumherum ist ganz viel leerer Raum. Auf der Vorschau sah es deutlich größer aus.',
        'expected' => ['PLAYBOOK-018'], 'forbidden' => ['PLAYBOOK-008', 'PLAYBOOK-014', 'POLICY-017']],
    'paraphrase: stamp in the middle' => ['group' => 'private-looxis-de', 'products' => ['3d-glass-photo'], 'text' => 'Das Paar auf meinem Glasquader sieht aus wie eine Briefmarke in der Mitte, man erkennt kaum was.',
        'expected' => ['PLAYBOOK-018'], 'forbidden' => ['PLAYBOOK-014', 'POLICY-017']],
    'reseller: end customer not satisfied' => ['group' => 'reseller', 'products' => ['3d-glass-photo'], 'text' => 'Unser Kunde ist nicht zufrieden: das Glasbild wirkt irgendwie matt und die Gestaltung ist nicht so wie in unserem Muster.',
        'expected' => ['PLAYBOOK-019'], 'forbidden' => ['PLAYBOOK-014', 'POLICY-017', 'PLAYBOOK-003']],
    'negative: too dark' => ['group' => 'private-looxis-de', 'products' => ['3d-glass-photo'], 'text' => 'Das Motiv im 3D-Glas ist viel zu dunkel, man erkennt kaum etwas.',
        'expected' => ['PLAYBOOK-009'], 'forbidden' => ['PLAYBOOK-018', 'PRODUCT-006']],
    'negative: shadow gap' => ['group' => 'private-looxis-de', 'products' => ['3d-glass-photo'], 'text' => 'Am Arm der Person fehlt ein Stück, da ist eine Lücke im Glas.',
        'expected' => ['PLAYBOOK-008'], 'forbidden' => ['PLAYBOOK-018', 'PRODUCT-006']],
    'nothing fits' => ['group' => 'private-looxis-de', 'products' => [], 'text' => 'Ich wollte nur sagen, dass mir Ihre Internetseite sehr gut gefällt.',
        'no_additions' => true, 'forbidden' => ['PLAYBOOK-018', 'PLAYBOOK-019', 'PLAYBOOK-014', 'POLICY-017', 'PLAYBOOK-008', 'PLAYBOOK-013']],
    'rights without a trigger word' => ['group' => 'private-looxis-de', 'products' => [], 'text' => 'Können Sie mir das Gesicht von Micky Maus auf eine Tasse drucken?',
        'expected' => ['POLICY-017', 'PLAYBOOK-014'], 'forbidden' => ['PLAYBOOK-018']],
    'own logo in a quote' => ['group' => 'private-looxis-de', 'products' => [], 'text' => 'Wir brauchen 200 Tassen mit unserem Firmenlogo für eine Veranstaltung.',
        'expected' => ['PROCESS-010'], 'forbidden' => ['POLICY-017', 'PLAYBOOK-014', 'TONE-007', 'PROCESS-006']],
    'own logo, order with file' => ['group' => 'reseller', 'products' => [], 'text' => 'Bitte bedrucken Sie 50 Kugelschreiber mit unserem Firmenlogo, die Datei hängt an.',
        'forbidden' => ['POLICY-017', 'PLAYBOOK-014', 'TONE-007']],
    'two concerns, one suppressed hit' => ['group' => 'private-looxis-de', 'products' => [], 'text' => 'Wir möchten 200 Tassen mit unserem Logo bestellen. Außerdem würden wir gern eine bekannte Zeichentrickfigur drucken.',
        'expected' => ['POLICY-017'], 'forbidden' => ['PLAYBOOK-018']],
    'invoice without the word' => ['group' => 'private-looxis-de', 'products' => [], 'text' => 'Ich brauche einen Beleg über meine Bestellung für die Steuer.',
        'expected' => ['POLICY-019'], 'forbidden' => ['POLICY-020', 'PLAYBOOK-018']],
    'unclear damage to the lunchbox' => ['group' => 'private-looxis-de', 'products' => ['lunchbox-wood-lid'], 'text' => 'Ein Stück meiner Lunchbox ist abgebrochen.',
        'forbidden' => ['PLAYBOOK-013', 'PLAYBOOK-018']],
    'italian without translation' => ['group' => 'private-amazon', 'products' => ['3d-glass-photo'], 'text' => 'Il motivo inciso nel vetro è molto piccolo e sembra perso nello spazio vuoto.',
        'expected' => ['PLAYBOOK-018'], 'forbidden' => ['PLAYBOOK-014', 'PLAYBOOK-008']],
    'italian with translation' => ['group' => 'private-amazon', 'products' => ['3d-glass-photo'], 'text' => 'Il motivo inciso nel vetro è molto piccolo e sembra perso nello spazio vuoto.', 'translation' => 'Das im Glas eingravierte Motiv ist sehr klein und wirkt verloren im leeren Raum.',
        'expected' => ['PLAYBOOK-018'], 'forbidden' => ['PLAYBOOK-014', 'PLAYBOOK-008']],
    'french without translation' => ['group' => 'private-amazon', 'products' => ['3d-glass-photo'], 'text' => 'Le motif gravé est trop petit, il paraît perdu au milieu du verre.',
        'expected' => ['PLAYBOOK-018'], 'forbidden' => ['PLAYBOOK-014', 'PLAYBOOK-008']],
    'french with translation' => ['group' => 'private-amazon', 'products' => ['3d-glass-photo'], 'text' => 'Le motif gravé est trop petit, il paraît perdu au milieu du verre.', 'translation' => 'Das eingravierte Motiv ist zu klein und wirkt mitten im Glas verloren.',
        'expected' => ['PLAYBOOK-018'], 'forbidden' => ['PLAYBOOK-014', 'PLAYBOOK-008']],
];
