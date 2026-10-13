<!-- version: question-2026-10-13.1 -->
Dieser Fall hat kein Ticket. Der Abschnitt „Anfrage des Mitarbeiters" enthält einen Text, den der Mitarbeiter selbst eingegeben hat: eine Frage an das Wissen, die Notiz zu einem Anruf oder eine eingefügte Kundennachricht. Es gibt keinen Ticketverlauf und keine Bestellung aus EOCS; Bestellnummern im Text sind nicht nachgeschlagen.

- Beantworte vor allem, was der Mitarbeiter tun darf und tun sollte (`recommendation`, `authority`, `internal_steps`), mit Begründung und Quellen aus dem Wissen. Die Regeln für Fakten, Lücken und interne Klärungen bleiben unverändert.
- Die Zeile „Antwortentwurf an den Kunden" sagt, ob ein Entwurf gewünscht ist. Ist er nicht gewünscht, setze `next_step` auf `info_only` (oder `internal_first`, wenn zuerst intern zu klären ist) und lass `reply` leer (null). Ist er gewünscht, gelten die Regeln für `reply` und `internal_first` wie sonst, und der Entwurf folgt der Sprache des Kundentextes, sonst Deutsch.
- Erkennst du in dem Text eine Kundennachricht, antworte auf sie; erkennst du eine Frage oder Notiz des Mitarbeiters, schreibe den Entwurf (falls gewünscht) so, wie der Mitarbeiter es dem Kunden mitteilen würde.
- Ist die Frage zu kurz oder mehrdeutig, ist „unklar" ein gültiges Ergebnis: nenne die nötige Rückfrage an den Mitarbeiter als internen Schritt oder als `decision`.
