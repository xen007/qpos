<?php
return [
 'title'=>'Alertes externes critiques','notice'=>'Désactivées par défaut. Ces destinataires sont rattachés à l’administrateur global qui enregistre ce formulaire. Activation pour les événements futurs seulement ; aucun changement aux destinataires des synthèses quotidiennes.',
 'email'=>'Email','sms'=>'SMS (Twilio)','whatsapp'=>'WhatsApp (Twilio)','enabled'=>'Activer ce canal','destinations'=>'Destinataires (liste)','destination_help'=>'Emails séparés par ligne, virgule ou point-virgule. Téléphones au format international, par exemple +2376XXXXXXXX. Vingt destinataires maximum par canal.',
 'locale'=>'Langue des messages','kinds'=>'Alertes à transmettre','consent'=>'J’autorise les destinataires ci-dessus à recevoir ces alertes et je confirme leur accord pour SMS/WhatsApp.',
 'consent_required'=>'Confirmez les destinataires et leur accord avant activation.','invalid'=>'Vérifiez les types, destinataires et canaux activés.',
 'ready'=>'Configuration présente, réception non vérifiée','unconfigured'=>'Transport non configuré','disabled'=>'Désactivé',
 'history'=>'Livraisons externes','channel'=>'Canal','state'=>'État','attempts'=>'Tentatives','reference'=>'Référence',
 'authenticated_link'=>'Consulter après connexion','minimal'=>'Le détail financier et les données personnelles restent dans l’application avec vos droits.',
 'template_help'=>'WhatsApp exige un modèle approuvé FR/EN. Variables : 1 alerte, 2 boutique, 3 référence, 4 lien authentifié. Identifiants et secrets se configurent hors de cette page, jamais en base.',
 'accepted_notice'=>'Accepté signifie pris en charge par SMTP ou le fournisseur ; cela ne prouve pas la réception. Ambigu : vérifier auprès du fournisseur, aucun renvoi automatique.',
 'states'=>['pending'=>'En attente','retry'=>'Reprise planifiée','unconfigured'=>'Non configuré','sending'=>'En cours','accepted'=>'Accepté par le transport','delivered'=>'Livré selon le fournisseur','ambiguous'=>'Confirmation incertaine','failed'=>'Échec','cancelled'=>'Annulé'],
 'external_preferences'=>'Mes canaux externes','preferences_notice'=>'Applicable uniquement aux destinataires autorisés dans les réglages globaux. Aucun destinataire ni droit n’est ajouté ici.',
];
