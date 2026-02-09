import sys
import phonenumbers
from phonenumbers import geocoder, carrier, number_type, PhoneNumberType
import json

def check(numero, code_pays):
    #
    #Analyse et formate un numéro de téléphone en fonction du code ISO du pays.
    #
    #Args:
    #    numero (str): Le numéro de téléphone sans l'indicatif de pays.
    #    code_pays (str): Le code ISO à deux lettres du pays (par ex. 'FR' pour la France).
    #
    #Returns:
    #    str: Détails sur le numéro en format JSON.
    #
    try:
        # Parse le numéro avec le code pays
        numero_parse = phonenumbers.parse(numero, code_pays)
        # Validation
        est_valide = phonenumbers.is_valid_number(numero_parse)
        if est_valide:
            # Formatage
            # Parse le numéro avec le code pays
            # Récupération de l'indicatif téléphonique
            indicatif_pays = numero_parse.country_code
            format_international = phonenumbers.format_number(numero_parse, phonenumbers.PhoneNumberFormat.INTERNATIONAL)
            format_national = phonenumbers.format_number(numero_parse, phonenumbers.PhoneNumberFormat.NATIONAL)
            type_numero = number_type(numero_parse)
            type_humain = {
                PhoneNumberType.MOBILE: "Mobile",
                PhoneNumberType.FIXED_LINE: "Ligne fixe",
                PhoneNumberType.FIXED_LINE_OR_MOBILE: "Ligne fixe ou mobile",
                PhoneNumberType.TOLL_FREE: "Numéro gratuit",
                PhoneNumberType.PREMIUM_RATE: "Numéro surtaxé",
                PhoneNumberType.SHARED_COST: "Coût partagé",
                PhoneNumberType.VOIP: "VoIP",
                PhoneNumberType.PERSONAL_NUMBER: "Numéro personnel",
                PhoneNumberType.PAGER: "Pager",
                PhoneNumberType.UAN: "Numéro UAN",
                PhoneNumberType.VOICEMAIL: "Boîte vocale",
                PhoneNumberType.UNKNOWN: "Inconnu"
            }.get(type_numero, "Inconnu")
            # Création du dictionnaire de résultats
            resultat = {
                "valide": est_valide,
                "international": format_international,
                "national": format_national,
                "type": type_humain,
                "ctype": type_numero
            }
        else:
            resultat = {"valide": False, "message": "Le numéro est invalide."}
    except phonenumbers.NumberParseException as e:
        resultat = {"valide": False, "erreur": str(e)}
    # Retourner les résultats au format JSON
    return json.dumps(resultat, ensure_ascii=False, indent=2)

# ===== Entrée CLI =====
if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(json.dumps({"error": "Usage: phoneNumber.py <phone> <country>"}))
        sys.exit(1)

    phone = sys.argv[1]
    country = sys.argv[2]

    result = check(phone, country)
    print(result)
