
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

    //récupération de l'id et de l'objet JSON
    var idContact = $(this).attr('id');
    var contactObject =  JSON.parse($('#contact-'+idContact).val());

    //Peupler la modal avec les informations
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