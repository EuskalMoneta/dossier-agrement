
var cptAdresse = 0;

//Fermeture + clean de la modale
function hideAdresseModal(){
    $('#adresseModal').modal('hide');
    //reinitialiser les champs à zero et fermer la modale
    $('.form-adresse').each(function() { $( this ).val( "" ); });

    $('#adresse-adresse option').each( function (){ $(this).remove(); });
    $('#adresse-guide').prop('checked', false);
    $('.adresse-delete').remove();
    $('#adresse-categories').val("").trigger("change");
    $('#adresse-categories-eskuz').val("").trigger("change");
}

//bouton fermer modale
$('#adresse-close').click(function (){
    hideAdresseModal();
});

//bouton supprimer adresse
$('#adresseModalFooter').on('click', '.adresse-delete', function (){

    var idAdresse = $(this).attr('id');
    $('#adresse-'+idAdresse).remove();
    $('.adresseCard'+idAdresse).remove();
    hideAdresseModal();

});

//Edition d'une card
$('#adresseContainer').on('click', '.adresse-edit', function (){
    hideAdresseModal();
    //récupération de l'id et de l'objet JSON
    var idAdresse = $(this).attr('id');
    var adresseObject =  JSON.parse($('#adresse-'+idAdresse).val());

    //Peupler la modal avec les informations
    $('#adresse-nom').val(adresseObject.nom);

    //si une adresse a été rentrée, on peuple le select avec une option par défaut
    if(adresseObject.adresse != ''){
        var adresseObj = JSON.parse(adresseObject.adresse);
        var newOption = new Option(adresseObj.text, adresseObj.id, true, true);
        $('#adresse-adresse').append(newOption).trigger('change');
    }

    $('#adresse-adresse-hidden').val(adresseObject.adresse);
    $('#adresse-complement').val(adresseObject.complement);
    $('#adresse-email').val(adresseObject.email);
    $('#adresse-instagram').val(adresseObject.instagram);
    $('#adresse-facebook').val(adresseObject.facebook);
    $('#adresse-telephone').val(adresseObject.telephone);
    $('#adresse-descriptif').val(adresseObject.descriptif);
    $('#adresse-horaires').val(adresseObject.horaires);
    $('#adresse-autresLieux').val(adresseObject.autresLieux);
    $('#adresse-guide').val(adresseObject.guide);
    $('#adresse-id').val(idAdresse);

    //select multiple categories
    $('#adresse-categories').val(adresseObject.categoriesAnnuaire).trigger("change");
    $('#adresse-categories-eskuz').val(adresseObject.categoriesAnnuaireEskuz).trigger("change");

    //checkbox
    if(adresseObject.guide){
        $('#adresse-guide').prop('checked', true);
    }
    $('#adresseModal').modal('show');

    $('#adresseModalFooter').prepend('<button type="button" class="btn btn-danger adresse-delete" id="'+idAdresse+'" style="margin-right:150px;">Supprimer</button>');
});

//Enregistrer les données d'un adresse
$('#adresse-submit').click(function (){

    //On récupère l'id si l'objet existe déjà, sinon id temporaire
    var idAdresse;
    var edition = false;
    if($('#adresse-id').val() != ''){
        idAdresse= $('#adresse-id').val();
        edition = true;
    } else {
        idAdresse = 'TEMP'+cptAdresse;
        cptAdresse++;
    }

    //on récupère les catégories de l'annuaire
    let categoriesAnnuaireSelect = [];
    $.each($('#adresse-categories').select2('data'), function( index, value ) {
        categoriesAnnuaireSelect.push(value.id);
    });

    //on récupère les catégories de l'annuaire Eskuz
    let categoriesAnnuaireEskuzSelect = [];
    $.each($('#adresse-categories-eskuz').select2('data'), function( index, value ) {
        categoriesAnnuaireEskuzSelect.push(value.id);
    });

    //sauvegarde des informations dans un objet
    var adresseObject = {
        nom: $('#adresse-nom').val(),
        adresse: $('#adresse-adresse-hidden').val(),
        email: $('#adresse-email').val(),
        facebook: $('#adresse-facebook').val(),
        instagram: $('#adresse-instagram').val(),
        telephone: $('#adresse-telephone').val(),
        descriptif: $('#adresse-descriptif').val(),
        complement: $('#adresse-complement').val(),
        horaires: $('#adresse-horaires').val(),
        autresLieux: $('#adresse-autresLieux').val(),
        categoriesAnnuaire: categoriesAnnuaireSelect,
        categoriesAnnuaireEskuz: categoriesAnnuaireEskuzSelect,
        guide: $('#adresse-guide').prop('checked'),
        id: idAdresse
    };

    //Template de la card à remplir
    const AdresseTemplate = ({nom, adresse, email, telephone, id}) => {
        var adresseText = '';
        if (adresse != '') {
            var adresseObj = JSON.parse(adresse);
            adresseText = adresseObj.text;
        }
        return `
    <div class="card-relation adresseCard${id}">
        <div class="d-flex justify-content-between">
            <div>
                <div class="d-flex">
                    <h3 class="h4-20bold">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                                                    <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                                                    <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                                </svg>
                    ${nom}
                    </h3>                    
                </div>
                <div class="paragraph-16reg">
                    ${adresseText}
                </div>               
                <div class="paragraph-16reg">
                    ${email}
                </div>
                 <div class="paragraph-16reg">
                    ${telephone}
                </div>
            </div>

            <div>
                <a id="${id}" class="adresse-edit linkText">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9.16663 1.66675H7.49996C3.33329 1.66675 1.66663 3.33341 1.66663 7.50008V12.5001C1.66663 16.6667 3.33329 18.3334 7.49996 18.3334H12.5C16.6666 18.3334 18.3333 16.6667 18.3333 12.5001V10.8334" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M13.3667 2.51663L6.80002 9.0833C6.55002 9.3333 6.30002 9.82497 6.25002 10.1833L5.89169 12.6916C5.75835 13.6 6.40002 14.2333 7.30835 14.1083L9.81669 13.75C10.1667 13.7 10.6584 13.45 10.9167 13.2L17.4834 6.6333C18.6167 5.49997 19.15 4.1833 17.4834 2.51663C15.8167 0.849966 14.5 1.3833 13.3667 2.51663Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12.425 3.45825C12.9834 5.44992 14.5417 7.00825 16.5417 7.57492" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
</a>
            </div>
        </div>
    </div>
    `
    };

    // Peupler le template et l'insérer dans la page
    var templating =
        [
            {
                nom: adresseObject.nom,
                adresse: adresseObject.adresse,
                email: adresseObject.email,
                telephone: adresseObject.telephone,
                id: adresseObject.id
            },
        ].map(AdresseTemplate).join('');


    if(edition){
        //on cherche l'ancienne div et on la remplace par la nouvelle
        $('.adresseCard'+idAdresse).replaceWith(templating);

    } else {
        $('#adresseContainer').append(templating);
        var inputHidden = '<input type="hidden" name="dataAdresses[adresse-'+idAdresse+']" id="adresse-'+idAdresse+'" value="">';
        $('#adresseInputsHidden').append(inputHidden);
    }

    //Mettre les informations dans le formulaire de la page
    var adresseJson = JSON.stringify(adresseObject);
    $('#adresse-'+idAdresse).val(adresseJson);

    hideAdresseModal();


});

//select 2 pour les categories
$('#adresse-categories').select2({
    dropdownParent: $('#annuaire-select'),
});

//select 2 pour les categories Eskuz
$('#adresse-categories-eskuz').select2({
    dropdownParent: $('#euskuz-select'),
});

//select 2 pour l'adresse postale
$('#adresse-adresse').select2({
    dropdownParent: $('#adresse-modal-select'),
    ajax: {
        url: "https://api-adresse.data.gouv.fr/search/",
        delay: 250,
        dataType: 'json',
        data: function (term) {
            var query = {
                q: term.term
            }
            return query;
        },
        processResults: function (data) {
            return {
                results: $.map(data.features, function (item) {
                    return {
                        text: item.properties.label,
                        /*text: item.properties.housenumber + ' ' + item.properties.street + ', ' + item.properties.city + ' (' + item.properties.postcode + ')',*/
                        id: item.properties.city,
                        lng: item.geometry.coordinates[0],
                        lat: item.geometry.coordinates[1],
                        address: item.properties.housenumber + ' ' + item.properties.street,
                        postcode: item.properties.postcode
                    }
                })
            };
        }
    },
    minimumInputLength: 3,
    placeholder: 'Saisir une adresse',
});


//permet de pré-remplir une adresse d'activité avec l'adresse principale
$('.remplir-adresse').on('click', function () {
    $('#adresse-nom').val($('#coordonnees_form_denominationCommerciale').val());
    $('#adresse-email').val($('#coordonnees_form_emailPrincipal').val());
    $('#adresse-telephone').val($('#coordonnees_form_telephone').val());
    $('#adresse-complement').val($('#coordonnees_form_complementAdresse').val());

    if($('#adressePrincipale').val() != ''){
        var adresseObj = JSON.parse($('#adressePrincipale').val());
        var newOption = new Option(adresseObj.text, adresseObj.id, true, true);
        $('#adresse-adresse').append(newOption).trigger('change');

        $('#adresse-adresse-hidden').val($('#adressePrincipale').val());
    }
});