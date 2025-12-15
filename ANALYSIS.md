# Anàlisi del plugin Arxiu Monòver

## Observacions generals
- **Dependència forta de models Gemini codificats**: La llista de models està fixa i inclou un model de preview (`gemini-2.5-flash-lite-preview-06-17`) que pot caducar o tenir preus diferents dels GA. No hi ha detecció automàtica de disponibilitat ni mapatge amb les tarifes oficials. 【F:includes/class-gemini-api.php†L25-L50】
- **Límit de quota i cost**: Els límits RPD/RPM estan codificats (15 RPM, 1.500 RPD) sense relació directa amb preus ni amb l'opció de desactivar l'ús del marge gratuït. L'estratègia de control de quota no diferencia entre models amb preus dispars. 【F:includes/class-gemini-api.php†L53-L58】【F:includes/class-queue-manager.php†L36-L41】
- **Processament en cua**: La cua usa batches fixes (5 ítems, cada 2 minuts) i retries lineals, però no hi ha cap lògica d'escalat dinàmic segons quota disponible o errors de cost. Tampoc hi ha purga automàtica periòdica de registres completats/fallats. 【F:includes/class-queue-manager.php†L36-L135】
- **Codificació d'imatges**: L'anàlisi llegeix el fitxer i l'envia en base64 dins del payload, cosa que pot incrementar costos de xarxa i memòria per imatges grans; Gemini admet càrrega via `fileData` però convé considerar compressió prèvia o límits de pes. 【F:includes/class-gemini-api.php†L99-L135】
- **Prompt unificat**: El prompt demana JSON complet i fixa l'estructura; no hi ha validació estricta ni reducció d'output tokens, i es demanen 4.096 tokens per resposta encara que la majoria d'anàlisis no els necessiten. 【F:includes/class-gemini-api.php†L130-L148】

## Propostes de millora
1. **Configuració de models basada en disponibilitat i cost**
   - Fer que la llista de models provingui d'opcions guardades i exposar-la a Configuració amb preus orientatius (enllaç a https://ai.google.dev/gemini-api/docs/pricing). Permetre marcar quins models estan autoritzats per evitar usar previews o models cars per accident.
   - Afegir un "selector intel·ligent" que triï automàticament el model més barat disponible segons el tipus de tasca (flash-lite per classificació ràpida, flash per anàlisi completa) i la configuració de cost màxim per crida.
2. **Control de quota i cost granular**
   - Fer que els límits `RPM/RPD` siguin configurables i diferenciats per model, amb la possibilitat de desactivar l'ús de la capa gratuïta (p.ex. bloquejar processament si no hi ha crèdits prepagats o si el cost estimat supera un pressupost diari).
   - Implementar un comptador de cost estimat per batch usant tarifes del model seleccionat i detenir la cua quan s'assoleixi el topall diari/mensual definit per l'usuari.
3. **Optimització de la cua**
   - Ajust dinàmic del `batch_size` i de l'interval segons quota restant i disponibilitat (reduir freqüència quan s'acosta al límit; augmentar quan hi ha marge i el cost és acceptable).
   - Afegir purge job programat (p.ex. diari) que elimini entrades `completed/failed` antigues per mantenir la taula petita i el dashboard àgil.
   - Introduir backoff exponencial en els retries i marcar errors permanents per evitar cicles infinits en fitxers problemàtics.
4. **Reducció de payload a Gemini**
   - Comprimir/escalejar la imatge abans d'enviar-la o usar una versió més petita (thumbnail) quan només cal classificació, per reduir cost i latència.
   - Baixar `maxOutputTokens` a un valor ajustat (p.ex. 1024) i només augmentar-lo si el mode "anàlisi detallada" està activat, minimitzant tokens facturables.
5. **Robustesa del prompt i validació**
   - Afegir validació de JSON retornat abans d'emmagatzemar-lo i reintentar amb un prompt curt si la resposta és invàlida, per evitar reprocessats manuals.
   - Versionar el prompt (p.ex. `prompt_version` dins la resposta) per poder migrar o reanalitzar attachments quan hi haja canvis d'estructura.

## Funcionalitats noves suggerides
- **Pressupost i mode segur**: Mòdul que permet establir un pressupost diari/mensual; el sistema pausa automàticament la cua quan s'assoleix el límit i envia notificacions a l'admin.
- **Analytics d'ús**: Dashboard amb histogrames de models utilitzats, cost estimat per dia i taxa d'errors per causa (quota, mida, format).
- **Qualitat de dades**: Validació en guardar metadades (p.ex. gènere correspon a taxonomia) i avisos quan falten camps clau per completar l'Arxiu.
- **Mode offline/manual**: Possibilitat d'entrar resultats manualment quan no es vulgui consumir API, mantenint el flux d'edició sense dependència externa.
