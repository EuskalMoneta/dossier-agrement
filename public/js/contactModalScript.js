
var cptContact = 0;

//Fermeture de la modal
function hideContactModal(){
    $('#contactModal').modal('hide');
    //reinitialiser les champs à zero et fermer la modal
    $('.form-contact').each(function() { $( this ).val( "" ); });
    $('#contact-interlocuteur').prop('checked', false);
    $('.contact-delete').remove();
}

//bouton fermer modal
$('#contact-close').click(function (){
    hideContactModal();
});

//bouton supprimer contact
$('#contactModalFooter').on('click', '.contact-delete', function (){

    var idContact = $(this).attr('id');
    $('#contact-'+idContact).remove();
    $('.contactCard'+idContact).remove();
    hideContactModal();

});

//Edition d'une card
$('#contactContainer').on('click', '.contact-edit', function (){
    hideAdresseModal();
    //récupération de l'id et de l'objet JSON
    var idContact = $(this).attr('id');
    var contactObject =  JSON.parse($('#contact-'+idContact).val());

    //Peupler la modal avec les informations
    $('#contact-civilite').val(contactObject.civilite);
    $('#contact-nom').val(contactObject.nom);
    $('#contact-prenom').val(contactObject.prenom);
    $('#contact-email').val(contactObject.email);
    $('#contact-telephone').val(contactObject.telephone);
    $('#contact-fonction').val(contactObject.fonction);
    $('#contact-interlocuteur').val(contactObject.interlocuteur);
    $('#contact-id').val(idContact);

    if(contactObject.interlocuteur){
        $('#contact-interlocuteur').prop('checked', true);
    }
    $('#contactModal').modal('show');

    $('#contactModalFooter').prepend('<button type="button" class="btn btn-danger contact-delete" id="'+idContact+'" style="margin-right:150px;">Supprimer</button>');
});

//Enregistrer les données d'un contact
$('#contact-submit').click(function (){

    //On récupère l'id si l'objet existe déjà, sinon id temporaire
    var idContact;
    var edition = false;
    if($('#contact-id').val() != ''){
        idContact= $('#contact-id').val();
        edition = true;
    } else {
        idContact = 'TEMP'+cptContact;
        cptContact++;
    }

    //sauvegarde des informations dans un objet
    var contactObject = {
        civilite: $('#contact-civilite').val(),
        nom: $('#contact-nom').val(),
        prenom: $('#contact-prenom').val(),
        email: $('#contact-email').val(),
        telephone: $('#contact-telephone').val(),
        fonction: $('#contact-fonction').val(),
        interlocuteur: $('#contact-interlocuteur').prop('checked'),
        id: idContact
    };

    //Template de la card à remplir
    const ContactTemplate = ({ nom, prenom, email, telephone, fonction, interlocuteur, id }) => {
        var ico = '';
        if(interlocuteur){
            ico = '<img src="images/ico-interlocuteur.png" width="30">';
        }
        return `
    <div class="card-relation contactCard${id}">
        <div class="d-flex justify-content-between">
            <div>
                <div class="d-flex">
                    <h3 class="h4-20bold">${prenom} ${nom}</h3>
                    <div class="ms-2 paragraph-16reg">
                        - ${fonction}
                    </div>
                </div>
                <div class="paragraph-16reg">
                    ${email}
                </div>
                <div class="paragraph-16reg">
                    ${telephone}
                </div>
                <div class="paragraph-16reg">
                    ${ico}
                </div>
            </div>

            <div>
                <a id="${id}" class="contact-edit linkText">
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
                civilite: contactObject.civilite,
                nom: contactObject.nom,
                prenom: contactObject.prenom,
                email: contactObject.email,
                telephone: contactObject.telephone,
                fonction: contactObject.fonction,
                interlocuteur: contactObject.interlocuteur,
                id: contactObject.id
            },
        ].map(ContactTemplate).join('');

    if(edition){
        //on cherche l'ancienne div et on la remplace par la nouvelle
        $('.contactCard'+idContact).replaceWith(templating);

    } else {
        $('#contactContainer').append(templating);
        var inputHidden = '<input type="hidden" name="dataContacts[contact-'+idContact+']" id="contact-'+idContact+'" value="">';
        $('#contactInputsHidden').append(inputHidden);
    }

    //Mettre les informations dans le formulaire de la page
    var contactJson = JSON.stringify(contactObject);
    $('#contact-'+idContact).val(contactJson);

    hideContactModal();

});