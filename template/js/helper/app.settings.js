const BOOT_BOX_MESSAGE = 0;
const SWEET_ALERT_MESSAGE = 1;
const SNACKBAR = 2;

const GENDER_MALE = 1;
const GENDER_FEMALE = 2;

const HTTP_SUCCESS = 200;

const STATE_OPEN = 8;
const STATE_PAUSE = 6;
const STATE_CLOSE = -4;
const STATE_DELETE = 0;

const ERROR = 0;
const WARNING = 1;
const SUCCESS = 2;
const INFO = 3;

const MAX_IMAGE_SIZE = 2; //MegaByte
const MAX_DOCUMENT_SIZE = 5; //MegaByte

const APP_NAME = "Oluakpata 2020";


$.fn.hasAttr = function(name) {
    return this.attr(name) !== undefined;
};

$.fn.enable = function() {
    this.prop('disabled', false);
};

$.fn.disable = function() {
    this.tooltip('hide');
    this.prop('disabled', true);
};

/**
 *
 * @type {boolean}
 */
window.permitClick = true;

/**
 *
 * @param status
 */
window.setPermitClick = function (status) {
    window.permitClick = status;
};

/**
 *
 * @returns {boolean}
 */
window.getPermitClick = function () {
    return window.permitClick;
};

/**
 *
 * @param variable
 * @returns {boolean}
 */
window.isArray = function (variable) {
    return Array.isArray(variable);
};

/***
 *
 * @param variable
 * @returns {boolean}
 */
window.isEmpty = function (variable) {
    let string = variable.toString();
    return (
        variable === false
        || variable === null
        || string === "0"
        || string === ""
        || string === " "
    );
};

/**
 *
 * @param variable
 * @returns {boolean}
 */
window.isEmptyString = function (variable) {
    return variable === " ";
};

/**
 *
 * @param variable
 * @returns {boolean}
 */
window.isString = function (variable) {
    return typeof variable === "string";
};

/**
 *
 * @param url
 * @return {*}
 */
window.baseUrl = function(url) {
    return ($("meta[name='base-url']").attr('content') + ('/' + url).replace(/\/\//g, '/'));
};

/**
 *
 * @param variable
 * @returns {Array}
 */
window.toArray = function (variable) {
    let list = [];
    for (let x in variable) {
        if (variable.hasOwnProperty(x)) {
            list[x] = variable[x];
        }
    }
    return list;
};

/**
 *
 * @param variable
 * @returns {string}
 */
window.ucFirst = function (variable) {
    variable = variable.trim();
    return (variable.substring(0, 1).toUpperCase() +
        variable.substring(1, variable.length).toLowerCase());
};

/**
 *
 * @param variable
 * @returns {string}
 */
window.getType = function (variable) {
    return typeof variable;
};

/**
 *
 * @param variable
 * @returns {boolean}
 */
let isBoolean = function (variable) {
    return typeof variable === "boolean";
};

/**
 *
 * @param variable
 * @returns {boolean}
 */
window.isNumeric = function (variable) {
    return isNaN(variable) === false;
};

/**
 *
 * @param variable
 * @returns {boolean}
 */
window.isObject = function (variable) {
    return typeof variable === "object";
};


/**
 *
 * @param array
 * @returns {object}
 */
window.arrayShuffle = function (array) {
    if (!is_array(array)) throw "array_shuffle expects an array";
    let size = array.length, x;
    for (x in array) {
        let j = Math.floor((Math.random() * size));
        let random = array[j];
        if (array.hasOwnProperty(x)) {
            array[j] = array[x];
            array[x] = random;
        }
    }
    return array;
};

/**
 *
 * @param variable
 * @returns {boolean}
 */
window.isUndefined = function (variable) {
    return typeof variable === "undefined";
};

/**
 *
 * @param object
 * @returns {string}
 */
window.serializeMessage = function (object) {
    let message = "", x, count = 0;
    for (x in object) {
        if (object.hasOwnProperty(x)) {
            if (isEmpty(object[x])) continue;
            // language=HTML
            message += (!isEmpty(message) ? "<br>" : "") +
                (++count + ". " + object[x]);
        }
    }
    return message;
};

/**
 *
 * @param title
 * @param message
 * @param status
 * @param type
 * @param confirmBtnTxt
 * @param confirmBtnCallback
 * @param cancelBtnTxt
 * @param cancelBtnCallback
 */
window.showMessage = function (title, message, status, type, confirmBtnTxt,
                               confirmBtnCallback, cancelBtnTxt, cancelBtnCallback) {

    confirmBtnTxt = isString(confirmBtnTxt) ? confirmBtnTxt : "Ok";

    if (type === BOOT_BOX_MESSAGE) {

        let button = (
            isString(cancelBtnTxt) ?
                {
                    main: {
                        label: confirmBtnTxt,
                        className: "btn-primary no-border-radius",
                        callback: (confirmBtnCallback instanceof Function ? confirmBtnCallback : null)
                    },
                    warning: {
                        label: cancelBtnTxt,
                        className: "btn-warning",
                        callback: (cancelBtnCallback instanceof Function ? cancelBtnCallback : null)
                    }
                } :
                {
                    main: {
                        label: confirmBtnTxt,
                        className: "btn-primary no-border-radius",
                        callback: (confirmBtnCallback instanceof Function ? confirmBtnCallback : null)
                    }
                }
        );

        bootbox.dialog({
            title: title,
            message: message,
            buttons: button
        });

    } else if (type === SWEET_ALERT_MESSAGE) {

        swal({
            title: title,
            text: message,
            type: (status === SUCCESS ? "success" :
                (status === ERROR ? "error" : (status === INFO ? "info" : "warning"))),
            confirmButtonClass: (status === SUCCESS ? "btn-primary no-border-radius" :
                (status === ERROR ? "btn-danger" : (status === INFO ? "btn-info" : "btn-warning"))),
            confirmButtonText: confirmBtnTxt,
            showCancelButton: isString(cancelBtnTxt),
            cancelButtonText: cancelBtnTxt,
            closeOnConfirm: true
        }, (confirmBtnCallback instanceof Function ? function (isConfirm) {
            confirmBtnCallback(isConfirm);
        } : null));

    } else if (type === SNACKBAR) {
        snackbar({
            message: message,
            // button: {
            //     label: confirmBtnTxt,
            //     className: "btn-primary no-border-radius",
            //     callback: (confirmBtnCallback instanceof Function ? confirmBtnCallback : null)
            // }
        });
    }
};

/**
 *
 * @param bytes
 * @param decimals
 * @returns {*}
 */
window.convertBytes = function (bytes, decimals) {
    if (parseInt(bytes) === 0) return '0 Bytes';
    let k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'],
        i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals || 2)) + sizes[i];
};

/**
 *
 * @param megaBytes
 * @param decimals
 * @returns {string}
 */
window.convertMegaBytes = function (megaBytes, decimals) {
    return Math.round((megaBytes * (1024 * 1024))).toFixed((decimals || 2));
};

/**
 *
 * @param txt
 * @return {string}
 */
window.isTyping = function (txt) {
    return '<div class="ellipsis-holder">' +
        '<span>' + txt + '</span>' +
        '<div class="ellipsis">' +
        '<div></div><div></div>' +
        '<div></div><div></div>' +
        '</div></div>';
};

/**
 *
 * @returns {string}
 */
window.addLoader = function () {
    return '<div class="ripple-loader-holder">' +
        '<div class="ripple-loader"><div></div>' +
        '<div></div></div></div>';

};

/**
 *
 * @param parentElement
 */
window.removeLoader = function (parentElement) {
    parentElement.find('.ripple-loader-holder').detach();
};

/**
 *
 * @param callback
 * @returns {*|jQuery}
 */
window.loadingFailed = function (callback) {
    return $('<div>').attr('class', 'col-md-12 text-center loading-failed').append(
        $('<h3>').attr('class', 'title').html("Loading seems to be taking a while.")
    ).append(
        $('<p>').html("This might be caused by a bad network connection or " +
            APP_NAME + " might be experiencing a temporary hiccup.")
    ).append(
        $('<hr>')
    ).append(
        $('<a>').css('cursor', 'pointer').html('Try again').click(function () {
            $(this).parent().detach();
            callback();
        })
    );
};

/**
 *
 * @param callback
 * @returns {*|jQuery}
 */
window.emptyContent = function (callback) {
    return $('<div>').attr('class', 'empty-content').append(
        $('<img>').attr('src', 'template/assets/images/sad-face.png')
    ).append(
        $('<span>').html("No record found. Click to retry.")
    ).click(function () {
        $(this).detach();
        callback();
    })
};

/**
 *
 * @returns {string}
 */
window.dataEnd = function () {
    return '<div class="data-end">No more data</div>';
};

/**
 *
 * @param button
 * @param addText
 */
window.buttonWaiting = function (button, addText = false) {
    let loader = '<i class="fa fa-spinner fa-spin" style="font-size:' + button.css('font-size') +
        ';"></i>' + (addText === true ? ' Waiting' : '');
    button.attr('formerContent', button.html()).html(loader).prop('disabled', true);
};

/**
 *
 * @param button
 * @param addText
 */
window.buttonLoading = function (button, addText = false) {
    let loader = '<i class="fa fa-spinner fa-spin" style="font-size:' + button.css('font-size') +
        ';"></i>' + (addText === true ? ' Loading...' : '');
    button.attr('formerContent', button.hasAttr('formerContent') ? button.attr('formerContent') :
        button.html()).html(loader).prop('disabled', true);
};

/**
 *
 * @param button
 */
window.buttonDone = function (button) {
    button.html(button.attr('formerContent')).removeAttr('formerContent').prop('disabled', false);
};

/**
 *
 * @param holder
 */
window.loaderWaiting = function (holder) {
    let loader = '<div id="preloader" class="preloader-extra">' +
        '<div data-loader="dual-ring"></div>' +
        '<div data-loader="ring-text">Waiting...</div>' +
        '</div>';
    holder.prepend(loader);
};

/**
 *
 * @param holder
 */
window.loaderLoading = function (holder) {
    let loader = '<div id="preloader" class="preloader-extra">' +
        '<div data-loader="dual-ring"></div>' +
        '<div data-loader="ring-text">Loading...</div>' +
        '</div>';
    holder.find('#preloader').detach();
    holder.prepend(loader);
};

/**
 *
 * @param holder
 */
window.loaderDone = function (holder) {
    holder.find('#preloader').fadeOut(800, function () {
        $(this).detach();
    });
};

/**
 *
 * @type {NetBridge}
 */
window.net = NetBridge.getInstance();

/**
 *
 * @param status
 * @return {string}
 */
window.getAccountStatusLabel = (status) => {

    return status === 1 ?
        '<span class="bg-success text-white rounded-pill d-inline-block px-2 mb-0">' +
        '<i class="fas fa-check-circle"></i> Active</span>'
        : (status === 0 ?
            '<span class="bg-warning text-white rounded-pill d-inline-block px-2 mb-0">' +
            '<i class="fas fa-times-circle"></i> In Active</span>' :
            '<span class="bg-primary text-white rounded-pill d-inline-block px-2 mb-0">' +
            '<i class="fas fa-times-circle"></i> Suspended</span>')

};

/**
 *
 * @param key
 * @return {string}
 */
window.getLabel = function (key) {
    if (key === -1)
        return 'Disapproved';
    else if (key === 0)
        return 'Pending';
    else if (key === 1)
        return 'Approved';
    else return 'Unknown';
};

/**
 *
 * @param key
 * @return {string}
 */
window.getStatusTxt = function (key) {
    if (key === -1)
        return 'Disapproved';
    else if (key === 0)
        return 'Pending';
    else if (key === 1)
        return 'Approved';
    else return 'Unknown';
};

/**
 *
 * @param key
 * @return {string}
 */
window.getLabelHTML = function (key) {
    if (key === -1)
        return getStatusTxt(key) +
            ' <span class="text-danger text-3">' +
            '<i class="fas fa-times-circle"></i>' +
            '</span>';
    else if (key === 0)
        return getStatusTxt(key) +
            ' <span class="text-warning text-3">' +
            '<i class="fas fa-hourglass-half"></i>' +
            '</span>';
    else if (key === 1)
        return getStatusTxt(key) +
            ' <span class="text-success text-3">' +
            '<i class="fas fa-check-circle"></i>' +
            '</span>';
    else return getStatusTxt(key) +
            ' <span class="text-primary text-3">' +
            '<i class="fas fa-question-circle"></i>' +
            '</span>';
};

/**
 *
 * @param key
 * @return {string}
 */
window.getLabelIcon = function (key) {
    if (key === -1)
        return 'fas fa-times-circle';
    else if (key === 0)
        return 'fas fa-hourglass-half';
    else if (key === 1)
        return 'fas fa-check-circle';
    else return 'fas fa-question-circle';
};

/**
 *
 * @param data
 * @return {string}
 */
window.getBankAccountTmp = function (data) {

    let primaryAcct = '', primaryAcctLabel = '';

    // if (!isUndefined(data.bankID) && !isUndefined(data.primaryBankID)
    //     && data.bankID === data.primaryBankID) {
    //     primaryAcct = 'account-card-primary';
    //     primaryAcctLabel = '<p class="bg-light text-0 text-body font-weight-500 ' +
    //         'rounded-pill d-inline-block px-2 line-height-4 opacity-8 mb-0">Primary</p>';
    // }

    return '<div class="col-12 col-sm-6 mb-2 mt-2 bank-account-' + data.id + '">' +
        '<div class="account-card ' + primaryAcct + ' text-white rounded mb-4 mb-lg-0">' +
        '<div class="row no-gutters">' +
        '<div class="col-3 d-flex justify-content-center p-3">' +
        '<div class="my-auto text-center text-13"><i class="fa fa-university"></i>'
        + primaryAcctLabel +
        '</div>' +
        '</div>' +
        '<div class="col-9 border-left">' +
        '<div class="py-4 my-2 pl-4">' +
        '<p class="text-4 font-weight-500 mb-1">' + data.bank + '</p>' +
        '<p class="text-4 opacity-9 mb-1">' + data.accountNumber + '</p>' +
        '<p class="m-0">' + getLabel(data.status) +
        ' <span class="text-3"><i class="' + getLabelIcon(data.status) + '"></i></span>' +
        '</p>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<div class="account-card-overlay rounded">' +
        '<a href="#" data-target="#bank-account-details" onclick="viewBankAccountDetail(this, ' + data.id + ');"' +
        'data-toggle="modal" class="text-light btn-link mx-2">' +
        '<span class="mr-1"><i class="fas fa-share"></i></span>' +
        'More Details' +
        '</a>' +
        '<a href="#" class="text-light btn-link mx-2" onclick="deleteBankAccount(this, ' + data.id + ');">' +
        '<span class="mr-1"><i class="fas fa-minus-circle"></i></span>' +
        'Delete' +
        '</a>' +
        '</div>' +
        '</div>' +
        '</div>';
};

/**
 *
 * @return {string}
 */
window.getBankAccountDetailModalBodyTmp = function () {
    return '<div class="row no-gutters">' +
        '<div class="col-sm-5 d-flex justify-content-center bg-primary rounded-left py-4">' +
        '<div class="my-auto text-center">' +
        '<div class="text-17 text-white mb-3"><i class="fas fa-university"></i></div>' +
        '<h3 class="text-6 text-white my-3 bank pr-2 pl-2">Bank</h3>' +
        '<div class="text-4 text-white my-4 accountNumber">xxxxxxxxxx</div>' +
        '<p class="bg-light text-0 text-body font-weight-500 ' +
        'rounded-pill d-none px-2 line-height-4 mb-0 primary-account">Primary</p>' +
        '</div>' +
        '</div>' +
        '<div class="col-sm-7">' +
        '<h5 class="text-5 font-weight-400 m-3">Bank Account Details' +
        '<button type="button" class="close font-weight-400" data-dismiss="modal" ' +
        'aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
        '</h5>' +
        '<hr>' +
        '<div class="px-3">' +
        '<ul class="list-unstyled">' +
        '<li class="font-weight-500">Account Type:</li>' +
        '<li class="text-muted accountType">xxxxxxxxx</li>' +
        '</ul>' +
        '<ul class="list-unstyled">' +
        '<li class="font-weight-500">Account Name:</li>' +
        '<li class="text-muted accountName">xxxxxxxxx</li>' +
        '</ul>' +
        '<ul class="list-unstyled">' +
        '<li class="font-weight-500">Account Number:</li>' +
        '<li class="text-muted accountNumber">xxxxxxxxx</li>' +
        '</ul>' +
        '<ul class="list-unstyled">' +
        '<li class="font-weight-500">Bank Country:</li>' +
        '<li class="text-muted countryName">xxxxxxxxx</li>' +
        '</ul>' +
        '<ul class="list-unstyled">' +
        '<li class="font-weight-500">Status:</li>' +
        '<li class="text-muted status">xxxxxxx</li>' +
        '</ul>' +
        '<div class="row mt-3">' +
        '<div class="col-md-6 mb-3">' +
        '<button class="btn btn-sm btn-outline-success btn-block ' +
        'shadow-none primary-bank-account-btn"><span ' +
        'class="mr-1"><i class="fas fa-check-circle"></i></span>' +
        'Make Primary' +
        '</button>' +
        '</div>' +
        '<div class="col-md-6 mb-3">' +
        '<button class="btn btn-sm btn-outline-danger btn-block ' +
        'shadow-none delete-bank-account-btn">' +
        '<span><i class="fas fa-minus-circle"></i></span>' +
        'Delete Account' +
        '</button>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
};

/**
 *
 * @param data
 * @return {string}
 */
window.getCardBrandIcon = function (data) {

    let brandIcon = '';

    if (data['cardBrand'] === 1) {
        brandIcon = '../template/asset/image/payment/visa.png';
    } else if (data['cardBrand'] === 2) {
        brandIcon = '../template/asset/image/payment/verve.png';
    } else if (data['cardBrand'] === 3) {
        brandIcon = '../template/asset/image/payment/mastercard.png';
    }
    return brandIcon;
};

/**
 *
 * @param data
 * @return {string}
 */
window.getCardTmp = function (data) {

    return '<div class="col-12 col-sm-6 col-lg-4 mb-2 mt-2 card-' + data.id + '">' +
        '<div class="account-card text-white rounded p-3 mb-4 mb-lg-0">' +
        '<p class="text-4">' + data['cardNumber'] + '</p>' +
        '<p class="d-flex align-items-center">' +
        '<span class="account-card-expire text-uppercase d-inline-block opacity-6 mr-2">Valid<br> thru<br></span>' +
        '<span class="text-4 opacity-9">' + data['expiryDate'] + '</span>' +
        '<span class="bg-light text-0 text-body font-weight-500 rounded-pill ' +
        'px-2 line-height-4 opacity-8 ml-auto">' + getStatusTxt(data['status']) + '</span>' +
        '</p>' +
        '<p class="d-flex align-items-center m-0"><span class="text-uppercase font-weight-500">' + data['cardHolderName'] + '</span>' +
        '<img class="ml-auto" src="' + getCardBrandIcon(data) + '"' +
        ' alt="card" title="">' +
        '</p>' +
        '<div class="account-card-overlay rounded">' +
        '<a href="#" data-target="#edit-card-details" onclick="viewCardDetail(this, ' + data.id + ')" data-toggle="modal"' +
        '   class="text-light btn-link mx-2">' +
        '<span class="mr-1"><i class="fas fa-edit"></i></span>' +
        'Edit' +
        '</a>' +
        '<a href="#" class="text-light btn-link mx-2" onclick="deleteCard(this, ' + data.id + ')">' +
        '<span class="mr-1"><i class="fas fa-minus-circle"></i></span>' +
        'Delete' +
        '</a>' +
        '</div>' +
        '</div>' +
        '</div>';
};

/**
 *
 * @return {string}
 */
window.getCardDetailModalBodyTmp = function () {
    return '<form id="update-card" method="post">' +
        '<div class="form-group">' +
        '<label for="edircardNumber">Card Number</label>' +
        '<div class="input-group">' +
        '<div class="input-group-prepend"><span class="input-group-text"><img ' +
        'class="ml-auto" id="cardBrand" src="../template/asset/image/payment/visa.png"' +
        'alt="visa" title=""></span></div>' +
        '<input type="text" name="cardNumber" class="form-control" data-bv-field="edircardNumber"' +
        '   id="edircardNumber" disabled' +
        '   placeholder="Card Number">' +
        '</div>' +
        '</div>' +
        '<div class="form-row">' +
        '<div class="col-lg-6">' +
        '<div class="form-group">' +
        '<label for="editexpiryDate">Expiry Date</label>' +
        '<input id="editexpiryDate" type="text" class="form-control"' +
        '   data-bv-field="editexpiryDate" required name="expiryDate"' +
        '   placeholder="MM/YY">' +
        '</div>' +
        '</div>' +
        '<div class="col-lg-6">' +
        '<div class="form-group">' +
        '<label for="editcvvNumber">CVV <span class="text-info ml-1"' +
        ' data-toggle="tooltip"' +
        ' data-original-title="For Visa/Mastercard, the three-digit CVV number ' +
        'is printed on the signature panel on the back of the card immediately ' +
        'after the card\'s account number. For American Express, the four-digit ' +
        'CVV number is printed on the front of the card above the card account number."><i' +
        'class="fas fa-question-circle"></i></span></label>' +
        '<input id="editcvvNumber" type="password" class="form-control"' +
        '   data-bv-field="editcvvNumber" name="cvv" required' +
        '   placeholder="CVV (3 digits)">' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<div class="form-group">' +
        '<label for="editcardHolderName">Card Holder Name</label>' +
        '<input type="text" class="form-control" data-bv-field="editcardHolderName"' +
        '   id="editcardHolderName" name="cardHolderName" required' +
        '   placeholder="Card Holder Name">' +
        '</div>' +
        '<input type="hidden" name="id" value=""/>' +
        '<input type="hidden" name="track" value=""/>' +
        '<input type="hidden" name="csrf" value=""/>' +
        '<button class="btn btn-primary btn-block" type="submit">Update Card</button>' +
        '</form>' +
        '<div class="row mt-3">' +
        '<div class="col-md-6 mb-3">' +
        '<button class="btn btn-sm btn-outline-success btn-block ' +
        'shadow-none primary-card-btn"><span ' +
        'class="mr-1"><i class="fas fa-check-circle"></i></span>' +
        'Make Primary' +
        '</button>' +
        '</div>' +
        '<div class="col-md-6 mb-3">' +
        '<button class="btn btn-sm btn-outline-danger btn-block ' +
        'shadow-none delete-card-btn">' +
        '<span><i class="fas fa-minus-circle"></i></span>' +
        'Delete Card' +
        '</button>' +
        '</div>' +
        '</div>';
};