# Kontrolli tulemus 08.11.2026

test.nvc.ee kontaktivorm on Zone'is aktiveeritud. Tootmisdomeeni ega WordPressi ei muudetud.

Läbitud kontrollid:
- PHP 8.5.12 süntaksikontroll: send.php ja mõlemad PHP testifailid.
- 35 isoleeritud handleri kontrolljuhtu: valideerimine, päisesüst, CSRF,
  võõras origin/host, HTTPS nõue, aegunud/korduskasutatud token, lõksväli,
  saatmispiirangud, vigane privaatkaust, saatmise edu ja tõrked.
  Need kontrollid ei saatnud tegelikke e-kirju.
- JavaScripti süntaks ja kõigi 15 HTML-lehe kohalikud lingid.
- Kõik 15 HTML-lehte avanesid testserveris ning kasutasid saidi CSS-i.
- HTTP kontaktileht suunab HTTPS-ile.
- Serverist WebFTP kaudu alla laaditud send.php kattub kontrollitud lähtekoodiga.
- Üks päris proovipäring kontaktivormi kaudu aadressile info@nvc.ee:
  kasutajale kuvatakse saatmiskinnitus; Zone'i logis staatus sent ning
  saaja meiliserver võttis kirja vastu. Postkasti kausta ega kirja avamist ei kontrollitud.

Paigaldus: testkataloogi .htaccess määrab SetEnv abil NVC_CONTACT_ENABLED=1
ja NVC_CONTACT_STORAGE tee. Privaatkaust loodi väljapoole veebijuurt õigustega 0700.
Zone'i halduses: test-alamdomeen kasutab PHP 8.5 ning HTTP -> HTTPS suunamist.

Muudatused esitatakse GitHubis eraldi haru ja PR-ina; ühendamist ega tootmisdomeenile avaldamist ei tehta.

Pakett nvc-test-zone.zip sisaldab töötavat Zone'i .htaccess seadistust.
Pakett nvc-source-review.zip sisaldab ka juhendeid, teste ja töövoogu.


