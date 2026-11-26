import type { Locale } from '@/i18n'

export type LegalPageKey = 'legal' | 'terms' | 'privacy'

interface LegalSection {
  heading: string
  paragraphs: string[]
}

interface LegalDocument {
  title: string
  description: string
  sections: LegalSection[]
}

/**
 * Trame des pages légales. Le texte définitif est fourni et validé par le propriétaire
 * (spec : rédaction non incluse) ; les passages entre crochets sont à compléter.
 */
export const legalDocuments: Record<LegalPageKey, Record<Locale, LegalDocument>> = {
  legal: {
    fr: {
      title: 'Mentions légales',
      description: 'Éditeur du site, hébergeur et contact.',
      sections: [
        { heading: 'Éditeur du site', paragraphs: ['[Nom ou raison sociale], [forme juridique], [adresse].', 'SIRET : [numéro]. Directeur de la publication : [nom].', 'Contact : [adresse e-mail], [téléphone].'] },
        { heading: 'Hébergement', paragraphs: ['[Nom de l’hébergeur], [adresse], [téléphone]. Serveur situé dans l’Union européenne.'] },
        { heading: 'Location meublée de tourisme', paragraphs: ['Les numéros d’enregistrement exigés par la commune figurent sur la fiche de chaque logement concerné.'] },
        { heading: 'Propriété intellectuelle', paragraphs: ['Les textes et photos de ce site appartiennent à [titulaire des droits]. Toute reproduction sans autorisation est interdite.'] },
      ],
    },
    en: {
      title: 'Legal notice',
      description: 'Site publisher, host and contact.',
      sections: [
        { heading: 'Publisher', paragraphs: ['[Name or company name], [legal form], [address].', 'SIRET: [number]. Publication director: [name].', 'Contact: [e-mail address], [phone].'] },
        { heading: 'Hosting', paragraphs: ['[Host name], [address], [phone]. Server located in the European Union.'] },
        { heading: 'Furnished tourist rental', paragraphs: ['Registration numbers required by the municipality are shown on the page of each apartment concerned.'] },
        { heading: 'Intellectual property', paragraphs: ['The texts and photos on this site belong to [rights holder]. Any reproduction without permission is prohibited.'] },
      ],
    },
  },
  terms: {
    fr: {
      title: 'Conditions générales de vente',
      description: 'Réservation, prix, paiement et annulation des séjours.',
      sections: [
        { heading: 'Objet', paragraphs: ['Les présentes conditions régissent la location de courte durée des logements meublés proposés sur ce site par [nom du loueur].'] },
        { heading: 'Réservation', paragraphs: ['La réservation est ferme dès la confirmation du paiement, envoyée par e-mail avec son récapitulatif.'] },
        { heading: 'Prix et taxe de séjour', paragraphs: ['Le prix affiché avant le paiement comprend l’hébergement, les frais de ménage, les options choisies et la taxe de séjour due par les adultes. Il est payé en totalité à la réservation.'] },
        { heading: 'Paiement', paragraphs: ['Le paiement s’effectue par carte sur une page sécurisée par Stripe. Aucune donnée de carte n’est conservée par le loueur.'] },
        { heading: 'Annulation', paragraphs: ['Les conditions d’annulation propres à chaque logement sont affichées sur sa fiche et rappelées avant le paiement. [Préciser les barèmes flexible, modéré et strict retenus.]'] },
        { heading: 'Arrivée, départ et séjour', paragraphs: ['Les horaires d’arrivée et de départ figurent sur la fiche du logement. [Règlement intérieur, dépôt de garantie éventuel, nombre maximal d’occupants.]'] },
        { heading: 'Réclamations et médiation', paragraphs: ['[Coordonnées du service client et du médiateur de la consommation compétent.]'] },
      ],
    },
    en: {
      title: 'Terms and conditions',
      description: 'Booking, price, payment and cancellation of stays.',
      sections: [
        { heading: 'Purpose', paragraphs: ['These terms govern the short-term rental of the furnished apartments offered on this site by [owner name].'] },
        { heading: 'Booking', paragraphs: ['A booking is confirmed once payment is confirmed, sent by e-mail with its summary.'] },
        { heading: 'Price and tourist tax', paragraphs: ['The price shown before payment includes accommodation, cleaning fee, chosen options and the tourist tax due by adults. It is paid in full when booking.'] },
        { heading: 'Payment', paragraphs: ['Payment is made by card on a page secured by Stripe. No card data is kept by the owner.'] },
        { heading: 'Cancellation', paragraphs: ['The cancellation terms of each apartment are shown on its page and repeated before payment. [Specify the flexible, moderate and strict schedules.]'] },
        { heading: 'Check-in, check-out and stay', paragraphs: ['Check-in and check-out times are shown on the apartment page. [House rules, security deposit if any, maximum number of occupants.]'] },
        { heading: 'Complaints and mediation', paragraphs: ['[Contact details of the customer service and of the competent consumer mediator.]'] },
      ],
    },
  },
  privacy: {
    fr: {
      title: 'Politique de confidentialité',
      description: 'Données collectées, finalités, durées de conservation et droits.',
      sections: [
        { heading: 'Responsable du traitement', paragraphs: ['[Nom du loueur], [adresse], [adresse e-mail].'] },
        { heading: 'Données collectées et finalités', paragraphs: ['Pour gérer votre réservation : identité, adresse e-mail, téléphone, pays, dates de séjour et messages. Pour répondre à vos questions : les informations du formulaire de contact. Base légale : exécution du contrat ou mesures précontractuelles prises à votre demande.'] },
        { heading: 'Durée de conservation', paragraphs: ['[Durées retenues : données des voyageurs anonymisées après … ; pièces comptables conservées … ans.]'] },
        { heading: 'Destinataires et sous-traitants', paragraphs: ['Stripe (paiement), [hébergeur du serveur], [fournisseur d’e-mails], Cloudflare (protection contre les robots), Sentry (suivi des erreurs techniques).'] },
        { heading: 'Cookies et mesure d’audience', paragraphs: ['Ce site n’utilise pas de cookie publicitaire. La mesure d’audience est anonyme et sans cookie. Un cookie technique sert uniquement à la sécurité des formulaires.'] },
        { heading: 'Vos droits', paragraphs: ['Vous pouvez demander l’accès, la rectification, l’effacement ou la portabilité de vos données, ou vous opposer à leur traitement, en écrivant à [adresse e-mail]. Vous pouvez aussi saisir la CNIL.'] },
      ],
    },
    en: {
      title: 'Privacy policy',
      description: 'Data collected, purposes, retention periods and rights.',
      sections: [
        { heading: 'Data controller', paragraphs: ['[Owner name], [address], [e-mail address].'] },
        { heading: 'Data collected and purposes', paragraphs: ['To manage your booking: identity, e-mail address, phone, country, stay dates and messages. To answer your questions: the contact form details. Legal basis: performance of the contract or pre-contractual steps taken at your request.'] },
        { heading: 'Retention period', paragraphs: ['[Retention periods: guest data anonymised after …; accounting records kept for … years.]'] },
        { heading: 'Recipients and processors', paragraphs: ['Stripe (payment), [server host], [e-mail provider], Cloudflare (bot protection), Sentry (technical error tracking).'] },
        { heading: 'Cookies and audience measurement', paragraphs: ['This site uses no advertising cookies. Audience measurement is anonymous and cookie-free. A technical cookie is only used to secure forms.'] },
        { heading: 'Your rights', paragraphs: ['You may request access, rectification, erasure or portability of your data, or object to its processing, by writing to [e-mail address]. You may also contact the CNIL.'] },
      ],
    },
  },
}

export const legalPaths: Record<LegalPageKey, string> = {
  legal: '/mentions-legales',
  terms: '/cgv',
  privacy: '/confidentialite',
}
