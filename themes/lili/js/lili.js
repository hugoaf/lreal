$(document).ready(function () {
    
    let variantes = window.variantes;
    
    console.log("ready!");
    const ID = $('a.snipcart-add-item').data('item-id');
    const ITEM_URL = $('a.snipcart-add-item').data('item-url');
    var opcionesFaltantes = '';

   // falta validar si el color no esta disabled

    function validarOpciones() {
        opcionesFaltantes = '';
        if ($('.talla').size() > 0 && $('.talla.selected').size() < 1) {
            $('a.snipcart-add-item').addClass('disabled');
            opcionesFaltantes += ' ' + $('.talla').data('name') + ' ';
        } else if ($('.color').size() > 0 && $('.color.selected').size() < 1) {
            $('a.snipcart-add-item').addClass('disabled');
            opcionesFaltantes += ' ' + $('.color').data('name') + ' ';
        } else {
            $('a.snipcart-add-item').removeClass('disabled');
            opcionesFaltantes = '';
        }
        return opcionesFaltantes;
    }

    $('a.snipcart-add-item').on('click', function (e) {
        if (validarOpciones()) {
            console.log('Opciones faltantes: ' + opcionesFaltantes);
            alert(SELECT_MSG + ': ' + opcionesFaltantes);
            e.stopPropagation();
            e.preventDefault();
        }
    });


    validarOpciones();


    $(document).on('click', '.talla', function () {
        $(this).parent().find('.talla').removeClass('selected');
        $(this).addClass('selected');
        var talla = $(this).data('talla-text');
        $('.talla-text').html(' ' + $(this).data('name') + ': ' + talla);

        $('a.snipcart-add-item').data('talla', talla);
        
        

        $('#colores>div').addClass('disabled');
        $('#colores>div').removeClass('enabled');
        $('#colores>div').removeClass('selected');
      
        console.log("clicked talla: " + talla);
        for (let i = 0; i < variantes.length; i++) {
           var col = variantes[i].color;
           if( variantes[i].talla == talla) {
                console.log(col);
                $('#colores').find('#color-'+col).removeClass('disabled');
                $('#colores').find('#color-'+col).addClass('enabled');
            } else {
                //$('#colores').find('#color-'+col).addClass('disabled');
            }
           
        } 
        createTitle();
        createItemId();
        createItemMaxQuantity(talla);
        validarOpciones();
        
        // se estaablece el valor de la talla en el custom hidden field para enviar a snipcart
        $('a.snipcart-add-item').attr('data-item-custom2-value', talla);
        $('a.snipcart-add-item').data('item-custom2-value', tall);

    });

    /* no usada por el momento, color deshabilitado */
    $(document).on('click', '.color', function () {
        if (!$(this).hasClass('disabled')) {
            $(this).parent().find('.color').removeClass('selected');
            $(this).addClass('selected');
            var c = $(this).data('color-text');
            $('.color-text').html(' ' + $(this).data('name') + ': ' + c);

            $('a.snipcart-add-item').data('color', c);

            createTitle();
            createItemId();
            validarOpciones();            
            
            
        }


    });

    // al seleccionar talla y color, actualiza el boton de snipcart data
    function createItemId() {
        //console.log( "talla: " + $('a.snipcart-add-item').data('talla') );

        var t = $('a.snipcart-add-item').data('talla') ? $('a.snipcart-add-item').data('talla') : '';
        var c = $('a.snipcart-add-item').data('color') ? $('a.snipcart-add-item').data('color').replace(/\s/g, '') : '';
        var new_id = ID;
        t ? new_id += '-' + t : '';
        c ? new_id += '-' + c : '';
        $('a.snipcart-add-item').attr('data-item-id', new_id);
        $('a.snipcart-add-item').data('item-id', new_id);
        var new_url = ITEM_URL;
        t ? new_url += '/talla:' + t : '';
        c ? new_url += '/color:' + c : '';
        $('a.snipcart-add-item').attr('data-item-url', new_url);
        $('a.snipcart-add-item').data('item-url', new_url);

        console.log('Id: ' + $('a.snipcart-add-item').data('item-id'));
        console.log('Url: ' + $('a.snipcart-add-item').data('item-url'));
    }

    // al seleccionar la talla actualiza el titulo del producto en el boton snipcart
    function createTitle() {
        var titulo = $('h1').text() + $('.talla-text').text() + ' ' + $('.color-text').text();
        $('a.snipcart-add-item').attr('data-item-name', titulo);
        $('a.snipcart-add-item').data('item-name', titulo);
        console.log('Titulo: ' + $('a.snipcart-add-item').data('item-name'));
    }
    
    // al seleccionar la talla actualiza la cantidad disponible en el boton snipcart
    function createItemMaxQuantity(talla) {
        let max_quantity = 0;
        for (let i = 0; i < variantes.length; i++) {
           if( variantes[i].talla == talla) {
                max_quantity = variantes[i].cantidad
                break;
            }
           
        } 

        $('a.snipcart-add-item').attr('data-item-max-quantity', max_quantity);
        $('a.snipcart-add-item').data('item-max-quantity', max_quantity);
        console.log('Item Max Quantity: ' + $('a.snipcart-add-item').data('item-max-quantity'));
    }



    document.addEventListener('snipcart.ready', function () {

        console.log('snipcart ready');

        var count = Snipcart.api.items.count();

        $('#currency').val(Snipcart.api.cart.currency());


        Snipcart.subscribe('api.cart.ready', function () {

            console.log('api cart ready');

            $('#currency').val(Snipcart.api.cart.currency());
        });

        Snipcart.subscribe('api.cart.currency.changed', function (currency) {
            $('#currency').val(curency);
            console.log(currency);


        });

        /*
         $(function() {
         $('#currency').change(function() {
         Snipcart.api.cart.currency($(this).val());
         currency = Snipcart.api.cart.currency()
         document.cookie = "grav_snipcart_currency=" + currency + ";path=/";  // saving currency in cookie
         console.log('currency changed to: ' + currency)
         
         changeDisplayedCurrency(currency);
         
         });
         });
         */


        $(function () {
            $('#currency').on('click', '.opcion', function () {
                Snipcart.api.cart.currency($(this).data('value'));
                currency = Snipcart.api.cart.currency();
                document.cookie = "grav_snipcart_currency=" + currency + ";path=/";  // saving currency in cookie
                console.log('currency changed to: ' + currency);
                //$(this).parent().find('opcion').toggleClass('current');
                changeDisplayedCurrency(currency);

            });
        });



        cookie_currency = getCookie('grav_snipcart_currency');

        console.log('cookie currency: ' + cookie_currency);

        if (!cookie_currency) {
            Snipcart.api.cart.currency(language_currency);  // set the Snipcard currency
            $('#currency').val(language_currency).change(); // set the selector to current currency
            changeDisplayedCurrency(language_currency);
            console.log('currency de idioma establecido: ' + Snipcart.api.cart.currency());

        } else {
            Snipcart.api.cart.currency(cookie_currency);
            console.log('currency de cookie establecido: ' + Snipcart.api.cart.currency());
            changeDisplayedCurrency(cookie_currency);
            $('#currency').val(cookie_currency).change(); // set the selector to current currency

        }







    });



    function getCookie(key) {
        var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
        return keyValue ? keyValue[2] : null;
    }


    //Snipcart.DEBUG = false;


    $('div.config .toggler').on('click', function () {
        $(this).parent().find('.config-items').toggle()
    })

    function changeDisplayedCurrency(currency) {
        if (currency == 'mxn') {
            $('.precio_usd').hide()
            $('.precio_mxn').show()
            $('#currency .usd').removeClass('current')
            $('#currency .mxn').addClass('current')
        } else
        if (currency == 'usd') {
            $('.precio_usd').show()
            $('.precio_mxn').hide()
            $('#currency .mxn').removeClass('current')
            $('#currency .usd').addClass('current')

        }
    }



})


