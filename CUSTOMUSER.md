Per gli Users lo farei molto simile, ma con qualche cautela in più.

Direzione unica:

Users -> Contacts

Quindi l’utente CRM è la sorgente ufficiale. Il Contact viene creato/aggiornato solo quando salvi l’utente. Mai il contrario.

Aggiungerei in migration:

ALTER TABLE vtiger_users
ADD COLUMN self_contact_id INT(19) DEFAULT NULL;

Poi sul salvataggio utente, dopo il save standard, chiamerei una sync tipo:

$userModel->syncSelfContact();

Oppure meglio un helper dedicato, per non sporcare troppo Users:

Users_SelfContactSync_Helper::sync($userId);

Mapping minimo:

first_name     -> firstname
last_name      -> lastname
email1         -> email
phone_work     -> phone
phone_mobile   -> mobile
title          -> title
department     -> department
address_street -> mailingstreet
address_city   -> mailingcity
address_state  -> mailingstate
address_postalcode -> mailingzip
address_country -> mailingcountry

Se esiste l’Account proprietario creato prima, collegherei anche:

account_id -> vtiger_organizationdetails.self_account_id

Questa è una cosa molto utile: tutti i contatti interni diventano “persone della nostra azienda”.

Regola operativa:

1. Leggo vtiger_users.self_contact_id.
2. Se punta a un Contact attivo, aggiorno quello.
3. Se è vuoto, provo eventualmente a trovare un Contact con la stessa email.
4. Se lo trovo, lo aggancio.
5. Se non lo trovo, creo un nuovo Contact.
6. Salvo il Contact tramite Vtiger_Record_Model, non SQL diretto.
7. Aggiorno vtiger_users.self_contact_id.

Campi minimi obbligatori:

lastname
assigned_user_id

Per lastname, se manca davvero, userei fallback:

last_name oppure user_name

Per assigned_user_id, metterei l’utente stesso se attivo, altrimenti admin.

Gestione utenti disattivati: per ora non cancellerei né disattiverei il Contact. Aggiornerei comunque il contatto, oppure al massimo in una fase successiva potremmo marcare un campo custom tipo “Utente CRM disattivo”. Non lo
farei subito.

Rischi principali:

- duplicati per email;
- utenti senza email;
- workflow Contact che partono quando salvi un utente;
- salvataggi massivi utenti che generano molti workflow;
- permessi se il salvataggio avviene da CLI o setup.

Per questo partirei minimo:

vtiger_users.self_contact_id
sync su salvataggio User
mapping nome/cognome/email/telefono/mobile
link all’Account proprietario se presente
nessuna sync inversa
nessuna cancellazione contatti

Così ottieni l’obiettivo: ogni User può avere un Contact reale e workflowabile, con riferimento stabile tramite self_contact_id, senza cambiare il modello mentale del CRM.
