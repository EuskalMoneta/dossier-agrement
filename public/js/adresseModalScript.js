
var cptAdresse = 0;

//Fermeture de la modale
function hideAdresseModal(){
    $('#adresseModal').modal('hide');
    //reinitialiser les champs à zero et fermer la modale
    $('.form-adresse').each(function() { $( this ).val( "" ); });
    $('#adresse-guide').prop('checked', false);
    $('.adresse-delete').remove();
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

    //récupération de l'id et de l'objet JSON
    var idAdresse = $(this).attr('id');
    var adresseObject =  JSON.parse($('#adresse-'+idAdresse).val());

    //Peupler la modal avec les informations
    $('#adresse-nom').val(adresseObject.nom);
    $('#adresse-adresse').val(adresseObject.adresse);
    $('#adresse-email').val(adresseObject.email);
    $('#adresse-telephone').val(adresseObject.telephone);
    $('#adresse-descriptif').val(adresseObject.descriptif);
    $('#adresse-horaires').val(adresseObject.horaires);
    $('#adresse-autresLieux').val(adresseObject.autresLieux);
    $('#adresse-guide').val(adresseObject.guide);
    $('#adresse-id').val(idAdresse);

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

    //sauvegarde des informations dans un objet
    var adresseObject = {
        nom: $('#adresse-nom').val(),
        adresse: $('#adresse-adresse-hidden').val(),
        email: $('#adresse-email').val(),
        telephone: $('#adresse-telephone').val(),
        descriptif: $('#adresse-descriptif').val(),
        horaires: $('#adresse-horaires').val(),
        autresLieux: $('#adresse-autresLieux').val(),
        guide: $('#adresse-guide').prop('checked'),
        id: idAdresse
    };

    //Template de la card à remplir
    const AdresseTemplate = ({ nom, adresse, email, telephone, id }) => {
        var adresseObj = JSON.parse(adresse);
        console.log(adresseObj);
        return `
    <div class="card-relation adresseCard${id}">
        <div class="d-flex justify-content-between">
            <div>
                <div class="d-flex">
                    <h3 class="h4-20bold">${nom}</h3>                    
                </div>
                <div class="paragraph-16reg">
                    ${adresseObj.text}
                </div>
                <div class="paragraph-16reg">
                    ${telephone}
                </div>
                <div class="paragraph-16reg">
                    ${email}
                </div>
            </div>

            <div>
                <a id="${id}" class="adresse-edit linkText">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
  <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
  <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
</svg>
</a>
            </div>
        </div>
    </div>
    `};

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


$('#adresse-adresse').select2({
    dropdownParent: $('#adresseModal'),
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
    minimumInputLength: 1,
    placeholder: 'Saisir une adresse',
});