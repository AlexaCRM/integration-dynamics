/*
 * Forms
 */

if(window.jQuery) {

function loadLookupData(popup, pagingcookie, pageNumber){
        //var lookupTypes = popup.children(".crm-lookup-lookup-types").attr("value");

        var lookupType = popup.find(".crm-lookup-lookuptype").children(":selected").attr("value");

        jQuery.ajax({
            url: wpcrm.ajaxurl,
            data: {
                'action':'retrieve_lookup_request',
                'lookupType' : lookupType,
                'pagingCookie' : pagingcookie,
                'pageNumber' : pageNumber
            },
            success:function(data) {
                popup.find(".crm-lookup-body-grid").html(data.data);

                if (data.pagingcookie){

                    popup.find(".crm-lookup-popup-next-page").attr("data-pagingcookie", popup.find(".crm-lookup-popup-next-page").attr("data-pagingcookie"));

                    popup.find(".crm-lookup-popup-next-page").attr("data-pagingcookie", data.pagingcookie);
                    popup.find(".crm-lookup-popup-prev-page").attr("data-pagingcookie", data.pagingcookie);
                }



                if (pageNumber > 1){
                    popup.find(".crm-lookup-popup-first-page").removeAttr("disabled");
                    popup.find(".crm-lookup-popup-prev-page").removeAttr("disabled");
                }else{
                    popup.find(".crm-lookup-popup-first-page").attr("disabled", "disabled");
                    popup.find(".crm-lookup-popup-prev-page").attr("disabled", "disabled");
                }

                if (data.morerecords !== "0"){
                    popup.find(".crm-lookup-popup-next-page").removeAttr("disabled");
                }else{
                    popup.find(".crm-lookup-popup-next-page").attr("disabled", "disabled");
                }

                popup.find(".body-row").first().addClass("selected-row");

                popup.find(".crm-lookup-popup-body-loader").fadeOut();


                bindPopupOnClick();

            },
            error: function(errorThrown){
                popup.find(".crm-lookup-body-grid").html(errorThrown);
            }
        });
}


function searchLookupData(popup, searchString){

        var lookupType = popup.find(".crm-lookup-lookuptype").children(":selected").attr("value");

        jQuery.ajax({
            url: wpcrm.ajaxurl,
            data: {
                'action':'search_lookup_request',
                'lookupType' : lookupType,
                'searchstring' : encodeURIComponent(searchString)
            },
            success:function(data) {

                popup.find(".crm-lookup-body-grid").html(data);

                popup.find(".body-row").first().addClass("selected-row");

                bindPopupOnClick();

                popup.find(".crm-lookup-popup-first-page").attr("disabled", "disabled");
                popup.find(".crm-lookup-popup-prev-page").attr("disabled", "disabled");
                popup.find(".crm-lookup-popup-next-page").attr("disabled", "disabled");

                popup.find(".crm-lookup-popup-body-loader").fadeOut();

            },
            error: function(errorThrown){
                console.log(errorThrown);
            }
        });

}

function bindPopupOnClick(){
        jQuery(".body-row").on('click', function(){
            if (!jQuery(this).hasClass("selected-row")){
                jQuery(this).parent().children(".body-row").removeClass("selected-row");
                jQuery(this).addClass("selected-row");
            }else{
                jQuery(this).parent().children(".body-row").removeClass("selected-row");
            }

            if (jQuery(this).parent().find(".selected-row").length < 1){

                jQuery(this).parent().parent().parent().parent().parent().find(".crm-popup-add-button").attr("disabled", "disabled");
            }else{
                jQuery(this).parent().parent().parent().parent().parent().find(".crm-popup-add-button").removeAttr("disabled", "disabled");
            }
        });
}




jQuery(document).ready(function () {
    jQuery('.crm-datepicker').datetimepicker({
        timepicker:false,
        format:'m/d/Y'
    });

    jQuery('.crm-datetimepicker').datetimepicker({
        format:'m/d/Y H:s'
    });


    if (typeof jQuery.widget !== 'undefined' && jQuery.isFunction(jQuery.widget)){

        (function (jQuery) {
            jQuery.widget("custom.combobox", {
                _create: function () {
                    this.wrapper = jQuery("<span>")
                            .addClass("custom-combobox")
                            .insertAfter(this.element);
                    this.element.hide();
                    this._createAutocomplete();
                    this._createShowAllButton();
                },
                _createAutocomplete: function () {
                    var selected = this.element.children(":selected"),
                            value = selected.val() ? selected.text() : "";
                    this.input = jQuery("<input>")
                            .appendTo(this.wrapper)
                            .val(value)
                            .attr("title", "")
                            .addClass("custom-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left")
                            .autocomplete({
                                delay: 0,
                                minLength: 0,
                                source: jQuery.proxy(this, "_source")
                            })
                            .tooltip({
                                tooltipClass: "ui-state-highlight"
                            });
                    this._on(this.input, {
                        autocompleteselect: function (event, ui) {
                            ui.item.option.selected = true;
                            this._trigger("select", event, {
                                item: ui.item.option
                            });
                        },
                        autocompletechange: "_removeIfInvalid"
                    });
                },
                _createShowAllButton: function () {
                    var input = this.input,
                            wasOpen = false;
                    jQuery("<a>")
                            .attr("tabIndex", -1)
                            .attr("title", "Show All Items")
                          /*  .tooltip()*/
                            .appendTo(this.wrapper)
                            .button({
                                icons: {
                                    primary: "ui-icon-triangle-1-s"
                                },
                                text: false
                            })
                            .removeClass("ui-corner-all")
                            .addClass("custom-combobox-toggle ui-corner-right")
                            .mousedown(function () {
                                wasOpen = input.autocomplete("widget").is(":visible");
                            })
                            .click(function () {
                                input.focus();
    // Close if already visible
                                if (wasOpen) {
                                    return;
                                }
    // Pass empty string as value to search for, displaying all results
                                input.autocomplete("search", "");
                            });
                },
                _source: function (request, response) {
                    var matcher = new RegExp(jQuery.ui.autocomplete.escapeRegex(request.term), "i");
                    response(this.element.children("option").map(function () {
                        var text = jQuery(this).text();
                        if (this.value && (!request.term || matcher.test(text)))
                            return {
                                label: text,
                                value: text,
                                option: this
                            };
                    }));
                },
                _removeIfInvalid: function (event, ui) {
    // Selected an item, nothing to do
                    if (ui.item) {
                        return;
                    }
    // Search for a match (case-insensitive)
                    var value = this.input.val(),
                            valueLowerCase = value.toLowerCase(),
                            valid = false;
                    this.element.children("option").each(function () {
                        if (jQuery(this).text().toLowerCase() === valueLowerCase) {
                            this.selected = valid = true;
                            return false;
                        }
                    });
    // Found a match, nothing to do
                    if (valid) {
                        return;
                    }
    // Remove invalid value
                    this.input
                            .val("")
                            .attr("title", value + " didn't match any item")
                            .tooltip("open");
                    this.element.val("");
                    this._delay(function () {
                        this.input.tooltip("close").attr("title", "");
                    }, 2500);
                    this.input.autocomplete("instance").term = "";
                },
                _destroy: function () {
                    this.wrapper.remove();
                    this.element.show();
                }
            });
        })(jQuery);

        jQuery(function () {
            jQuery(".alexacombobox").combobox();
        });

    }

    jQuery(".crm-popup-add-button").on('click', function(e){
        e.preventDefault();

        if (typeof jQuery(this).attr("disabled") == "undefined"){
            var popup = jQuery(this).parent().parent().parent();

            var id = popup.find(".body-row.selected-row").attr("data-entityid");
            var name = popup.find(".body-row.selected-row").attr("data-name");

            popup.parent().parent().find(".crm-lookup-textfield").attr("value", name);
            popup.parent().parent().find(".crm-lookup-hiddenfield").attr("value", id);

            popup.parent().parent().find(".crm-lookup-textfield-delete-value").show();
            popup.parent().parent().find(".crm-popup-remove-value-button").removeAttr("disabled");

            popup.parent().fadeOut();
        }

        return false;
    });

     jQuery(".crm-lookup-popup-overlay-bg, .crm-popup-cancel, .crm-popup-cancel-button").on('click',function(e){
        e.preventDefault();

        jQuery(".crm-lookup-popup-overlay").fadeOut();

        return false;
    });

    jQuery(".crm-lookup-textfield-button").on('click',function(e){
        jQuery(this).parent().parent().children(".crm-lookup-popup-overlay").fadeIn();

        var popup = jQuery(this).parent().parent();

        if (!popup.find(".lookup-table").length){

            popup.find(".crm-lookup-popup-body-loader").fadeIn();
            loadLookupData(popup);
        }

        if (popup.parent().parent().find(".crm-lookup-hiddenfield").attr("value") !== ""){
            popup.find(".crm-popup-remove-value-button").removeAttr("disabled");
        }

    });

    jQuery(".crm-popup-remove-value-button").on('click', function(e){
        e.preventDefault();

       if (typeof jQuery(this).attr("disabled") == "undefined"){
            var popup = jQuery(this).parent().parent().parent();

            popup.parent().parent().find(".crm-lookup-textfield").attr("value", "");
            popup.parent().parent().find(".crm-lookup-hiddenfield").attr("value", "");
            popup.parent().parent().find(".crm-lookup-textfield-delete-value").hide();

            popup.parent().fadeOut();

            jQuery(this).attr("disabled", "disabled");
       }

       return false;
    });

    jQuery(".crm-lookup-lookuptype").on("change", function(){
        var popup = jQuery(this).parent().parent().parent().parent().parent().parent();

        popup.find(".crm-lookup-popup-body-loader").fadeIn();

        loadLookupData(popup);
    });

    jQuery('.crm-lookup-searchfield-button').on('click', function(){
        var searchfield = jQuery(this).parent().children(".crm-lookup-searchfield");
        var popup = searchfield.parent().parent().parent().parent().parent().parent();

        popup.find(".crm-lookup-popup-body-loader").fadeIn();

        jQuery(this).hide();
        jQuery(this).parent().children(".crm-lookup-searchfield-delete-search").show();

        searchLookupData(popup, searchfield.val());

    });

    jQuery(".crm-lookup-searchfield-delete-search").on('click', function(){
        var searchfield = jQuery(this).parent().children(".crm-lookup-searchfield");
        var popup = searchfield.parent().parent().parent().parent().parent().parent();
        jQuery(this).hide();
        jQuery(this).parent().children(".crm-lookup-searchfield-button").show();
        searchfield.val('');

        popup.find(".crm-lookup-popup-body-loader").fadeIn();

        searchLookupData(popup, searchfield.val());

    });

    jQuery(".crm-lookup-textfield-delete-value").on('click', function(){
        jQuery(this).parent().children(".crm-lookup-textfield").val("");
        jQuery(this).parent().children(".crm-lookup-hiddenfield").val("");

        jQuery(this).hide();
    });


    jQuery(".crm-lookup-popup-first-page").on('click', function(e){
        e.preventDefault();

        if (typeof jQuery(this).attr("disabled") == "undefined"){

            var popup = jQuery(this).parent().parent().parent().parent().parent().parent().parent();

            popup.find(".crm-lookup-popup-body-loader").fadeIn();

            loadLookupData(popup);

            popup.find(".crm-lookup-popup-page-counter").html("1");

            jQuery(this).attr("disabled", "disabled");

        }

        return false;
    });

    jQuery(".crm-lookup-popup-prev-page").on('click', function(e){
        e.preventDefault();

        if (typeof jQuery(this).attr("disabled") == "undefined"){

            var popup = jQuery(this).parent().parent().parent().parent().parent().parent().parent();

            popup.find(".crm-lookup-popup-body-loader").fadeIn();

            var pagingCookie = jQuery(this).attr("data-pagingcookie");

            var page = parseInt(popup.find(".crm-lookup-popup-page-counter").html());

            if (page == 1){

            }else if (page == 2){

                loadLookupData(popup);

                popup.find(".crm-lookup-popup-page-counter").html("1");

            }else{

                popup.find(".crm-lookup-popup-page-counter").html(page - 1);

                loadLookupData(popup, pagingCookie, page -1);

            }

        }

        return false;
    });

    jQuery(".crm-lookup-popup-next-page").on('click', function(e){
        e.preventDefault();

        if (typeof jQuery(this).attr("disabled") == "undefined" ){

            var popup = jQuery(this).parent().parent().parent().parent().parent().parent().parent();

            popup.find(".crm-lookup-popup-body-loader").fadeIn();

            var pagingCookie = jQuery(this).attr("data-pagingcookie");

            var page = parseInt(popup.find(".crm-lookup-popup-page-counter").html());

            popup.find(".crm-lookup-popup-page-counter").html(page + 1);

            loadLookupData(popup, pagingCookie, page + 1);

        }

        return false;
    });

});

jQuery(document).keypress(function(e) {
    if(e.which == 13  && jQuery('.crm-lookup-searchfield').is(':focus')) {

        var searchfield = jQuery('.crm-lookup-searchfield:focus');
        var popup = searchfield.parent().parent().parent().parent().parent().parent();

        popup.find('.crm-lookup-searchfield-button').hide();
        popup.find(".crm-lookup-searchfield-delete-search").show();

        popup.find(".crm-lookup-popup-body-loader").fadeIn();

        searchLookupData(popup, searchfield.val());

    }
});

}
