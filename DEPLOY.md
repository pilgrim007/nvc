# NVC testkeskkond: Zone / PHP 8.2+

## Kontrollitud paigaldus 08.11.2026

test.nvc.ee kontaktivorm on Zone'is aktiveeritud ja kontrollitud PHP 8.5-ga.
Testserverisse paigaldatud .htaccess põhineb failil zone-test.htaccess; serveritee on kontole kohandatud.
See määrab vormi keskkonnamuutujad SetEnv abil ainult testkataloogis.
Privaatkaust nvc-contact-private asub testkataloogi kõrval, väljaspool
dokumendijuurt, õigustega 0700. Kontrollitud saatja- ja saajaadress on info@nvc.ee.
Zone'i saatmislogi kinnitas proovikirja staatuseks sent.

Uuendatud nvc-test-zone.zip sisaldab töötavat testserveri .htaccess faili.
See sisaldab selle Zone'i konto absoluutset kaustateed; teisel kontol tuleb
NVC_CONTACT_STORAGE väärtus kohandada. Vorm on endiselt piiratud test.nvc.ee-ga.
Ära asenda seda faili paketi üldise .htaccess näidisega, sest see lülitaks
vormi keskkonnamuutujate puudumisel välja. Tootmisele seda paketti ei paigaldata.

Muudatused on mõeldud ainult https://test.nvc.ee jaoks. Ära paigalda neid nvc.ee
WordPressi juurkausta. PR-i loomine ei avalda Zone'i serverisse midagi.

## Paigaldus
1. Tee testkataloogist varukoopia. Kasuta ainult alamdomeeni test.nvc.ee dokumendijuurt.
2. Laadi sinna repo juurkausta HTML-, CSS-, JS-, send.php, robots.txt ja .htaccess failid.
   index.html on avaleht. Säilita landing-variant-1.html vana URL-i jaoks.
   Ära laadi üles .git, tests, .github, DEPLOY.md ega preview-server.js.
3. Vali Zone'is PHP 8.2 või uuem ja luba PHP sessions ning mail().
   PHP fail peab käivituma, mitte lähtekoodina alla laadima.
4. Loo privaatne kirjutatav kataloog VÄLJASPOOL kõigi saitide dokumendijuuri,
   nt konto privaatkausta nvc-contact. Õigused 0700, sama omanik mis PHP protsessil.
   Ära kasuta 0777 ega laadi rate.json faili avalikku kausta.
5. Sea PHP protsessi keskkonnamuutujad (Zone'i haldus/toe abil):
   NVC_CONTACT_STORAGE = selle kataloogi absoluutne serveritee
   NVC_CONTACT_ENABLED = 1
   Kui neid ei saa halduses määrata, palu Zone'i toel seadistada need ainult test-alamdomeeni PHP-le.
   Vaikimisi on saatmine suletud (503), kuni seadistus on valmis.
   Sellel kontol on mõlemad väärtused juba määratud testkataloogi .htaccess-is
   SetEnv abil ja nende toimimine on päris saatmiskatsega kinnitatud.
6. Kontrolli SPF-i sobivust Zone'ist saatmisega ja DKIM-i olekut.
   Saatja/envelope on info@nvc.ee, saaja info@nvc.ee, vastuse aadress on valideeritud külastaja aadress.
   Saatja seadistus peab olema Zone'i kontol lubatud. Ära lisa repo failidesse paroole.
7. Kontrolli .htaccess toimimist (sh peidetud faili üleslaadimist), HTTP -> HTTPS
   suunamist ja X-Robots-Tag päist. noindex pole ligipääsukaitse; vajadusel lisa
   Zone'i kaudu paroolikaitse ainult test-alamdomeenile.

## Vastuvõtukontroll test.nvc.ee keskkonnas
- Ava / ja kõik menüülehed, kontrolli CSS-i ja mobiilimenüüd.
- HTTP kontaktileht suunab HTTPS-ile; vormi action on HTTPS POST.
- Oota pärast vormi laadimist vähemalt 3 sekundit. Saada üks selgelt märgitud
  proovipäring ja kinnita info@nvc.ee postkastis saabumine ning Reply-To toimimine.
  mail() õnnestumine kinnitab ainult serverile üleandmist, mitte postkasti saabumist.
- Kontrolli ka vigast e-posti, tühje/pikki välju, reavahetusega päiserünnet,
  massiivina saadetud välju, võõrast Origin-it, aegunud/korduskasutatud tokenit,
  täidetud website-välja, liiga kiiret saatmist ning saatmiste arvu piirangut.
- Simuleeri mail() tõrge eraldi testkeskkonnas: kasutaja peab saama veateate,
  mitte eduteate. Viga jätab vormi sisestused alles. Võrguvea korral automaatset
  kordussaatmist ei tehta, sest esialgse kirja tulemus võib olla teadmata.
- JavaScriptita ning GitHub Pages'is on vorm suletud ja näidatakse otsekontakti.
  Pages ei käivita PHP-d. preview-server.js on ainult staatiliste lehtede vaatamiseks.
- Kontrolli privaatkausta kirjutatavust; selle puudumisel peab saatmine ebaõnnestuma.

## Turvapiirid ja andmed
CSRF token, Secure/HttpOnly/SameSite cookie, range origin/host/HTTPS kontroll,
peidetud lõksväli, minimaalne täitmisaeg, ühe IP 60 sekundi paus ja 5 katset tunnis,
üleüldine 5 sekundi paus. Piirangud kasutavad REMOTE_ADDR-i, mitte võltsitavaid
forwarded päiseid; reverse proxy korral kinnita Zone'iga tegeliku IP seadistus.
IP-de HMAC-võtmed ja ajad hoitakse ühes lukustatud piiratud suurusega failis.
Vanad kirjed eemaldatakse järgmisel saatmiskatsel. Rakendus ei logi sisu ega
toor-IP-sid. Veebiserver ja Zone võivad oma teenuselogisid eraldi säilitada.
Tokenid on ühekordsed saatmiskatse jaoks; kuni viis vahelehte toetatud.
Need meetmed vähendavad rämpsposti, kuid sihitud boti vastu võib vaja minna lisakaitset.

## Tagasipööramine
Eemalda NVC_CONTACT_ENABLED või sea 0: vorm peatub ohutult ja kuvab otsekontakti.
Taasta vajadusel ainult testkataloogi varukoopia. Ära taasta mailto-vormi.
Tootmisele üleminek vajab eraldi ülevaatust: host/origin/action, indekseerimine,
ligipääsukaitse, vanad URL-id ja WordPressi üleminekuplaan.

Zone'i saatmisjuhend:
https://www.zone.ee/help/kb/kirjade-saatmine-veebiserverist/
PHP mail():
https://www.php.net/manual/en/function.mail.php

