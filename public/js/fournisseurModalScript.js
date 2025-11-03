
var cptFournisseur = 0;

//Fermeture + clean de la modale
function hideFournisseurModal(){
    $('#fournisseurModal').modal('hide');
    //reinitialiser les champs à zero et fermer la modale
    $('.form-fournisseur').each(function() { $( this ).val( "" ); });
    $('.form-fournisseur').each(function() { $( this ).html( "" ); });
    $('.fournisseur-delete').remove();
}

//bouton fermer modale
$('#fournisseur-close').click(function (){
    hideFournisseurModal();
});

//bouton supprimer fournisseur
$('#fournisseurModalFooter').on('click', '.fournisseur-delete', function (){
    var idFournisseur = $(this).attr('id');
    $('#fournisseur-'+idFournisseur).remove();
    $('.fournisseurCard'+idFournisseur).remove();
    hideFournisseurModal();
});

//Edition d'une card
$('#fournisseurContainer').on('click', '.fournisseur-edit', function (){
    hideFournisseurModal();

    //récupération de l'id et de l'objet JSON
    var idFournisseur = $(this).attr('id');
    var fournisseurObject =  JSON.parse($('#fournisseur-'+idFournisseur).val());

    //Peupler la modal avec les informations
    $('#fournisseur-entreprise').val(fournisseurObject.entreprise);
    $('#fournisseur-nom').val(fournisseurObject.nom);
    $('#fournisseur-prenom').val(fournisseurObject.prenom);
    $('#fournisseur-activite').val(fournisseurObject.activite);
    $('#fournisseur-status').val(fournisseurObject.status);

    if(fournisseurObject.adresse != ''){
        var fournisseurObj = JSON.parse(fournisseurObject.adresse);
        var newOption = new Option(fournisseurObj.text, fournisseurObj.id, true, true);
        $('#fournisseur-adresse').append(newOption).trigger('change');
    }
    $('#fournisseur-adresse-hidden').val(fournisseurObject.adresse);
    $('#fournisseur-email').val(fournisseurObject.email);
    $('#fournisseur-telephone').val(fournisseurObject.telephone);
    $('#fournisseur-commentaires').val(fournisseurObject.commentaires);
    $('#fournisseur-id').val(idFournisseur);

    $('#fournisseurModal').modal('show');
    $('#fournisseurModalFooter').prepend('<button type="button" class="btn btn-danger fournisseur-delete" id="'+idFournisseur+'" style="margin-right:150px;">Supprimer</button>');
});

//Enregistrer les données d'un fournisseur
$('#fournisseur-submit').click(function (){

    //On récupère l'id si l'objet existe déjà, sinon id temporaire
    var idFournisseur;
    var edition = false;
    if($('#fournisseur-id').val() != ''){
        idFournisseur= $('#fournisseur-id').val();
        edition = true;
    } else {
        idFournisseur = 'TEMP'+cptFournisseur;
        cptFournisseur++;
        $('#fournisseur-status').val('nouveau prospect')
    }

    //sauvegarde des informations dans un objet
    var fournisseurObject = {
        entreprise: $('#fournisseur-entreprise').val(),
        nom: $('#fournisseur-nom').val(),
        prenom: $('#fournisseur-prenom').val(),
        activite: $('#fournisseur-activite').val(),
        telephone: $('#fournisseur-telephone').val(),
        email: $('#fournisseur-email').val(),
        adresse: $('#fournisseur-adresse-hidden').val(),
        commentaires: $('#fournisseur-commentaires').val(),
        status: $('#fournisseur-status').val(),
        id: idFournisseur
    };

    //Template de la card à remplir
    const FournisseurTemplate = ({ entreprise, nom, prenom, activite, adresse, email, telephone, status, id }) => {
        var adresseText = '';
        if(adresse != ''){
            var adresseObj = JSON.parse(adresse);
            adresseText = adresseObj.id;
        }
        var classBadge = 'bg-success';
        if(status == 'nouveau prospect'){
            classBadge = 'bg-dark';
        }

        return `
    <div class="card-relation fournisseurCard${id}">
        <div class="d-flex justify-content-between">
            <div>
                <div class="d-flex">
                    <h3 class="h4-20bold mb-0">${entreprise}</h3>
                    
                    <div>
                    <span class="ms-3 badge ${classBadge}">${status}</span>
                    </div>
                </div>
                <div class="sousTitre-18bold">
                    ${activite} - ${adresseText}
                </div>
                <div class="paragraph-16reg mt-1">
                    ${prenom} ${nom}
                </div>
                <div class="paragraph-16reg">
                    ${email}
                </div>
                <div class="paragraph-16reg">
                    ${telephone}
                </div>


            </div>

            <div>
                <a  id="${id}" class="fournisseur-edit linkText">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.16663 1.66675H7.49996C3.33329 1.66675 1.66663 3.33341 1.66663 7.50008V12.5001C1.66663 16.6667 3.33329 18.3334 7.49996 18.3334H12.5C16.6666 18.3334 18.3333 16.6667 18.3333 12.5001V10.8334" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13.3667 2.51663L6.80002 9.0833C6.55002 9.3333 6.30002 9.82497 6.25002 10.1833L5.89169 12.6916C5.75835 13.6 6.40002 14.2333 7.30835 14.1083L9.81669 13.75C10.1667 13.7 10.6584 13.45 10.9167 13.2L17.4834 6.6333C18.6167 5.49997 19.15 4.1833 17.4834 2.51663C15.8167 0.849966 14.5 1.3833 13.3667 2.51663Z" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12.425 3.45825C12.9834 5.44992 14.5417 7.00825 16.5417 7.57492" stroke="#292D32" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
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
                entreprise: fournisseurObject.entreprise,
                nom: fournisseurObject.nom,
                prenom: fournisseurObject.prenom,
                activite: fournisseurObject.activite,
                adresse: fournisseurObject.adresse,
                email: fournisseurObject.email,
                telephone: fournisseurObject.telephone,
                status: fournisseurObject.status,
                id: fournisseurObject.id
            },
        ].map(FournisseurTemplate).join('');

    if(edition){
        //on cherche l'ancienne div et on la remplace par la nouvelle
        $('.fournisseurCard'+idFournisseur).replaceWith(templating);

    } else {
        $('#fournisseurContainer').append(templating);
        var inputHidden = '<input type="hidden" name="dataFournisseurs[fournisseur-'+idFournisseur+']" id="fournisseur-'+idFournisseur+'" value="">';
        $('#fournisseurInputsHidden').append(inputHidden);
    }

    //Mettre les informations dans le formulaire de la page
    var fournisseurJson = JSON.stringify(fournisseurObject);
    $('#fournisseur-'+idFournisseur).val(fournisseurJson);

    hideFournisseurModal();

});

//remplir l'input hidden avec le json de l'adresse postale
$('.js-city-ajax').on('select2:select', function (e) {
    var data = e.params.data;
    $(this).next().next().val(JSON.stringify(data));
});

//select 2 pour l'Adresse postale
$('#fournisseur-adresse').select2({
    dropdownParent: $('#fournisseurModal'),
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
                        id:  item.properties.id,
                        lng: item.geometry.coordinates[0],
                        lat: item.geometry.coordinates[1],
                        address: item.properties.housenumber + ' ' + item.properties.street,
                        postcode: item.properties.postcode,
                        city: item.properties.city,
                    }
                })
            };
        }
    },
    minimumInputLength: 3,
    placeholder: 'Saisir une adresse',
});