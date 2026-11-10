# Mobiiliparanduste kontroll 08.11.2026

Avalik https://test.nvc.ee/ kontrolliti reaalses sisseehitatud brauseris laiustel 320, 375, 390, 430 ja 768 px (kõrgus 900).

| Laius | Enne parandusi |
|---|---|
|320|dokumendi scrollWidth 404; h1 44,8 px; tekst ja CTA lõigatud|
|375|scrollWidth 404; h1 44,8 px; horisontaalne ülevool|
|390|scrollWidth 404; h1 44,8 px; horisontaalne ülevool|
|430|scrollWidth 415 (kerimisribaga sisu); h1 44,8 px; avalehe ülevoolu ei nähtud|
|768|scrollWidth 753 (kerimisribaga sisu); h1 46,08 px; avalehe ülevoolu ei nähtud|

Kõigil viiel laiusel oli mobiilimenüü juhtelement aria-hidden label ja päise CTA display:none.

Parandused: kõigi menüüga lehtede päris nupp aria-expanded/aria-controls olekuga, Escape sulgemine ja fookuse tagastus, väljaspool klõpsamise ja fookuse lahkumise sulgemine, lingi valimise ja laiuse muutmise oleku lähtestamine. JavaScriptita jäävad menüülingid nähtavaks. Mobiilimenüüs kontaktivormi CTA. Väiksem mobiili h1, pikkade sõnade murdmine, painduvamad grid-veerud ja kaardid, nähtav klaviatuurifookus ja vähendatud liikumise tugi. Menüü murdepunkt 1180 px väldib pika päise kokkusurumist.

Korduskontroll: 35 isoleeritud PHP handleri juhtu läbitud PHP 8.5.12-ga; päris kirju ei saadetud. contact.js ja navigation.js süntaks korras. 15 HTML-lehe lingikontroll läbitud enne kohaliku QA-lehe loomist. PHP 8.2 käivituskontrolli selles voorus ei tehtud.

Piirang: sisseehitatud brauser ei saanud kohalikku eelvaateserverit avada (ühenduse aegumine). Kohaliku Chrome'i juhtimine peatati, sest tööriist ei suutnud aktiivset URL-i usaldusväärselt tuvastada. Parandatud versiooni viie laiuse visuaalne ja interaktiivne kordustest jääb seetõttu tegemata. Avalikku Zone'i testserverit selles voorus ei uuendatud. Varasema REVIEW.md saatmistulemused on eelmise vooru tulemused, mitte uus saatmiskatse.

## Testkeskkonna uuendamine

1. Kasuta haru codex/secure-contact-zone-test (PR #1), mitte main-haru vana mailto-vormi.
2. Varunda ainult test.nvc.ee dokumendijuur. Laadi sinna muudetud HTML-failid, nvc-homepage.css ja navigation.js. Säilita töötav serveri .htaccess ja NVC_CONTACT_STORAGE/ENABLED seadistus. Ära kopeeri näidiskonfiguratsiooni pimesi üle.
3. Kontaktivormi esmapaigaldusel järgi DEPLOY.md: kontakt.html, contact.js, send.php; HTTPS; PHP 8.2+; session ja mail; kirjutatav privaatkaust väljaspool veebijuuri. Paroole pole vaja lähtekoodi lisada. GitHub Pages ei käivita PHP-d.
4. Ära laadi üles teste, juhendeid, arhiive ega kohalikku eelvaateserverit. Ära muuda nvc.ee ega WordPressi kaustu.
5. Tühjenda testlehe vahemälu. Kontrolli kõiki lehti viiel laiusel, menüü Enter/Space/Escape ja Tab, välist klõpsu, CTA sihtkohta, horisontaalset kerimist ning 200% suumi. Kontrolli ka 1180/1181 px murdepunkti.
6. Kontaktilehel peab token laadima ning vorm olema HTTPS POST. Üks selgelt märgistatud päris proovipäring vajab saatmist ja info@nvc.ee postkastis saabumise kontrolli; mail() edu kinnitab ainult meiliserverile üleandmist.

Tootmiskeskkonda ega WordPressi ei muudetud. PR jäi ühendamata.

GitHubi salvestamine selles voorus ebaõnnestus: nii Git tree kui contents API tagastasid 403 Resource not accessible by integration. Seega mobiiliparandused on ainult kohalikus paketis, mitte PR-is. PR #1 sisaldab varasemat kontaktivormi parandust.

## Tegelik paigaldus ja kordustest 08.11.2026

Mobiiliparandused paigaldati Zone WebFTP kaudu ainult /test kataloogi: 14 HTML-faili, navigation.js ja nvc-homepage.css. GitHubi haru on endiselt f96780a3; mobiiliparandused pärinevad kohalikust töökaustast. Serveri .htaccess, send.php ja contact.js jäid muutmata. Tootmise htdocs kausta ei muudetud.

Avaliku https://test.nvc.ee/ lõplik brauseritest kõrgusel 900 px:

|Viewport|Sisu clientWidth|scrollWidth|Menüü Enter / Escape|
|---|---|---|---|
|320|305|305|läbitud|
|375|360|360|läbitud|
|390|375|375|läbitud|
|430|415|415|läbitud|
|768|753|753|läbitud|

15 px erinevus tuleb nähtavast vertikaalsest kerimisribast. Horisontaalset ülevoolu ei ole. 320 px kokkuvõtte pealkirja 4 px jääkülevool parandati täiendavalt ja CSS laaditi uuesti serverisse.

12 alamlehte kontrolliti 320 px juures: kontakt, teenused, pädev mõõtja, aktsiisiladu, ISO juhtimissüsteemid, meist, ISO 14001, ISO 27001/E-ITS, ISO 45001, ISO 9001, mõõtja juhtimissüsteem ja toiduohutus. Kõigil clientWidth=scrollWidth=305 ja uus menüü aktiveeritud.

Mobiilimenüü CTA viis kontakt.html#contact-form juurde. Kontaktivormi ettevalmistus õnnestus, kuvati „Täida väljad ja saada päring.” ning saatmisnupp oli aktiivne. Uut päriskirja ei saadetud.

GitHubi ja serveri mobiiliversioonid ei ole veel sünkroonis: GitHubi salvestamist takistas eelmises voorus ühenduse kirjutusõigus (403). Paigaldus õnnestus kohalike failidega.

## GitHubi sünkroonimise kontroll
16 mobiiliparanduste faili võrreldi HTTPS kaudu testserveriga: kõik bait-baidilt võrdsed. contact.js ja nvc-homepage-redesign.html sisu on samuti võrdne pärast CRLF/LF reavahetuste normaliseerimist. Serveri kontopõhine .htaccess jääb eraldi seadistuseks; seda ei asendata repo näidisega. Muudatused salvestatakse olemasolevasse tööharusse ja PR-i #1, põhiharu ühendamata.
