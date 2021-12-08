/**
 * Nuvei Payments js component.
 *
 * @category Nuvei
 * @package  Nuvei_Checkout
 */

var nuveiSetPaymentInformation; // we will attach Magento setPaymentInformation method here
var nuveiMessageContainer; // we will attach Magento messageContainer here

var nuveiAgreementsConfig		= window.checkoutConfig ? window.checkoutConfig.checkoutAgreements : {};
var nuveiCheckoutSdkParams		= {};
var nuveiSelectedProvider		= '';
var nuveiOrderTotal				= 0;
var nuveiBillingCountry			= '';
var nuveiOrderFullName			= '';
var nuveiAgreementErrorMsg		= '';

/**
 * Get the code of the module.
 * 
 * @returns {String}
 */
function nuveiGetCode() {
	return 'nuvei';
};

/**
 * Validate checkout agreements
 *
 * @returns {Boolean}
 */
function nuveiValidateAgreement(hideError) {
	console.log('nuveiValidateAgreement()');

	var nuveiAgreementsInputPath	= '.payment-method._active div.checkout-agreements input';
	var isValid						= true;

	if (!nuveiAgreementsConfig.isEnabled
		|| jQuery(nuveiAgreementsInputPath).length === 0
	) {
	   return isValid;
	}

	jQuery(nuveiAgreementsInputPath).each(function (index, element) {
	   if (!jQuery.validator.validateSingleElement(element, {
		   errorElement: 'div',
		   hideError: hideError || false
	   })) {
		   isValid = false;
	   }
	});

	return isValid;
};

/**
 * Get most of the parameters for the Checkout call.
 * 
 * @returns {object}
 */
function getNuveiCheckoutParams() {
	return window.checkoutConfig.payment[nuveiGetCode()].nuveiCheckoutParams;
};

function nuveiGetSessionToken() {
	console.log('nuveiGetSessionToken()');
	
//	jQuery('body').trigger('processStart');
	
//	if(nuveiGetCode() != nuveiSelectedProvider) {
//		console.log('nuveiGetSessionToken() - slected payment method is not Nuvei');
//		jQuery('body').trigger('processStop');
//		return;
//	}

	if(nuveiCheckoutSdkParams.hasOwnProperty('sessionToken')) {
		console.log('nuveiGetSessionToken() - checkout data is already set');
//		jQuery('body').trigger('processStop');
		return;
	}

	// load Nuvei Checkout
	nuveiCheckoutSdkParams = getNuveiCheckoutParams();

	if(!nuveiCheckoutSdkParams.hasOwnProperty('amount')) {
		nuveiCheckoutSdkParams.amount = nuveiOrderTotal;
	}
	
	// call openOrder here and get the session token
	 jQuery.ajax({
		dataType: 'json',
		url: window.checkoutConfig.payment[nuveiGetCode()].getUpdateOrderUrl
	})
	.error(function(jqXHR, textStatus, errorThrown){
		// TODO show unexpected error
		console.log('nuveiLoadCheckout update order fail jqXHR', jqXHR);
		console.log('nuveiLoadCheckout update order fail textStatus', textStatus);
		console.log('nuveiLoadCheckout update order fail errorThrown', errorThrown);

		//window.location.reload();
		
//		jQuery('body').trigger('processStop');
		return;
	})
	.success(function(resp) {
		console.log('success', resp);
		nuveiLoadCheckout(resp);
		return;
	});
}

/**
 * Load the Chekout content into its container
 * 
 * @returns {void}
 */
function nuveiLoadCheckout(sessionTokenResp) {
	console.log('nuveiLoadCheckout() sessionTokenResp', sessionTokenResp);

	if(!sessionTokenResp.hasOwnProperty('sessionToken') || '' == sessionTokenResp.sessionToken) {
		console.log('nuveiLoadCheckout update order sessionToken problem, reload the page');
//				window.location.reload();
//		jQuery('body').trigger('processStop');
		return;
	}
	
	nuveiCheckoutSdkParams.sessionToken	= sessionTokenResp.sessionToken;
	nuveiCheckoutSdkParams.country		= nuveiBillingCountry;
	nuveiCheckoutSdkParams.fullName		= nuveiOrderFullName;

	console.log('nuveiLoadCheckout()', nuveiCheckoutSdkParams);

	nuveiCheckoutSdkParams.prePayment	= nuveiPrePayment;
	nuveiCheckoutSdkParams.onResult		= nuveiAfterSdkResponse;

	nuveiCheckoutSdk(nuveiCheckoutSdkParams);
//	jQuery('body').trigger('processStart');
	return;
};

/**
 * Use it as last check before complete the Order with the Checkout SDK.
 * 
 * @param {object} paymentDetails
 * @returns {Promise}
 */
function nuveiPrePayment(paymentDetails) {
	console.log('nuveiPrePayment()');
	
//	jQuery('body').trigger('processStart');
	
	return new Promise((resolve, reject) => {
		// validate user agreement
		if (!nuveiValidateAgreement()) {
			reject(jQuery.mage.__('Please, accept required agreement!'));
			jQuery('body').trigger('processStop');
		}
		else {
			nuveiUpdateOrder(resolve, reject);
		};
	});
};

function nuveiUpdateOrder(resolve, reject, secondCall = false) {
	var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
           if (xmlhttp.status == 200) {
				console.log('status == 200', xmlhttp.response);
				resolve();
           }
           else if (xmlhttp.status == 400) {
              console.log('There was an error 400');
			  reject();
           }
           else {
				console.log('something else other than 200 was returned');
			   
				if(!secondCall) {
					nuveiUpdateOrder(resolve, reject, true)
				}
				else {
					reject();
				}
           }
        }
    };

    xmlhttp.open("GET", window.checkoutConfig.payment[nuveiGetCode()].getUpdateOrderUrl, true);
    xmlhttp.send();
	
	
	
//	jQuery.ajax({
//		type: "GET",
//		url: window.checkoutConfig.payment[nuveiGetCode()].getUpdateOrderUrl,
//		dataType: 'json',
//		data: {},
//		success: function() {
//			console.log('nuvei success');
//		},
//		error: function(jqXHR, textStatus, errorThrown) {
//			console.log('nuvei error');
//			console.log('update order fail jqXHR', jqXHR);
//			console.log('update order fail textStatus', textStatus);
//			console.log('update order fail errorThrown', errorThrown);
//		},
//		complete: function(xhr, textStatus) {
//			console.log('nuvei complete method', xhr);
//			console.log('nuvei complete method', textStatus);
//			
//		} 
//	})
//	.fail(function(jqXHR, textStatus, errorThrown){
//		// TODO show unexpected error
//		console.log('update order fail jqXHR', jqXHR);
//		console.log('update order fail textStatus', textStatus);
//		console.log('update order fail errorThrown', errorThrown);
//
////				reject(jQuery.mage.__('Unexpected error, please try again!'));
//
//		reject();
//	})
//	.done(function(resp) {
//		console.log(resp);
//
//		if(resp.hasOwnProperty('sessionToken') && '' != resp.sessionToken) {
//			if(resp.sessionToken == nuveiCheckoutSdkParams.sessionToken) {
//				console.log('session token is ok');
////						return true;
//				resolve();
//			}
//
//			nuveiCheckoutSdkParams.sessionToken = resp.sessionToken;
//			nuveiCheckoutSdkParams.amount		= resp.amount;
//
//			console.log('resp.sessionToken != nuveiCheckoutSdkParams.sessionToken', nuveiCheckoutSdkParams.sessionToken);
//			nuveiLoadCheckout();
//			return false;
//		}
//
//		console.log('update order problem');
//		return false;
//	})
//		.complete(function (resp) {
//			try {
//				console.log('complete', resp);
//				console.log('complete responseJSON', resp.responseJSON);
//				console.log('complete responseJSON', resp.responseJSON.sessionToken);
////				console.log('complete status', resp.hasOwnProperty('status'));
////				return false;
//			}
//			catch(error) {}
//
////			if(resp.hasOwnProperty('responseJSON')
////				&& resp.responseJSON('sessionToken')
////				&& '' != resp.responseJSON.sessionToken
////				&& resp.responseJSON.sessionToken == nuveiCheckoutSdkParams.sessionToken
////			) {
////				return true;
////			}
////
////			return false;
//		})
//;
//	resolve();
}

/**
 * Here we receive the response from the Checkout SDK Order
 * @param {object} resp
 * @returns {void|Boolean}
 */
function nuveiAfterSdkResponse(resp) {
	console.log('nuveiAfterSdkResponse() resp', resp);

	// on unexpected error
	if(typeof resp == 'undefined'
		|| !resp.hasOwnProperty('result')
		|| !resp.hasOwnProperty('transactionId')
	) {
		if(!alert(jQuery.mage.__('Unexpected error, please try again later!'))) {
//			window.location.reload();
			jQuery('body').trigger('processStop');
			return;
		}
	}

	// on Declined
	if(resp.result == 'DECLINED') {
		if(!alert(jQuery.mage.__('Your Payment was DECLINED. Please try another payment method!'))) {
			jQuery('body').trigger('processStop');
			return;
		}
	}

	// when not Declined, but not Approved also
	if(resp.result != 'APPROVED' || isNaN(resp.transactionId)) {
		var respError = 'Error with your Payment. Please try again later!';

		if(resp.hasOwnProperty('errorDescription') && '' != resp.errorDescription) {
			respError = resp.errorDescription;
		}
		else if(resp.hasOwnProperty('reason') && '' != resp.reason) {
			respError = resp.reason;
		}

		if(!alert(jQuery.mage.__(respError))) {
//			window.location.reload();
			jQuery('body').trigger('processStop');
			return;
		}
	}

	// on Success, Approved
	window.location.href = window.checkoutConfig.payment[nuveiGetCode()].successUrl;
//	jQuery('#co-payment-form').attr('onsubmit', '');
//	jQuery('#co-payment-form').trigger('submit');
	jQuery('body').trigger('processStop');
	return;
};

define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Paypal/js/action/set-payment-method',
        'jquery.redirect',
        'ko',
        'Magento_Checkout/js/model/quote',
        'mage/translate',
		'Magento_Checkout/js/action/set-payment-information',
		'mage/validation'
    ],
    function(
        $,
        Component,
        setPaymentMethodAction,
        jqueryRedirect,
        ko,
        quote,
		setPaymentInformation,
        mage
    ) {
        'use strict';

//		console.log('setPaymentInformation', typeof (setPaymentInformation))
//		console.log('messageContainer', typeof (self.messageContainer))

        var self					= null;
		nuveiOrderFullName			= quote.billingAddress().firstname + ' ' + quote.billingAddress().lastname;
//		nuveiSetPaymentInformation	= setPaymentInformation;
//		nuveiMessageContainer		= self.messageContainer;
		
        return Component.extend({
            defaults: {
                template: 'Nuvei_Checkout/payment/nuvei',
                chosenApmMethod: '',
                countryId: ''
            },
			
			nuveiOrderTotal: 0,
			
			nuveiBillingCountry: '',
			
			nuveiSelectedProvider: '',
			
            initObservable: function() {
                self = this;
				
                self._super()
                    .observe([
                        'chosenApmMethod',
                        'countryId'
                    ]);
                   
				try {
					if(quote.paymentMethod._latestValue != null) {
						self.nuveiSelectedProvider = nuveiSelectedProvider = quote.paymentMethod._latestValue.method;
						self.scUpdateQuotePM();
					}
				}
				catch(_error) {
					console.error(_error);
				}
				
				self.nuveiOrderTotal		= nuveiOrderTotal
											= parseFloat(quote.totals().base_grand_total).toFixed(2);
				self.nuveiBillingCountry	= nuveiBillingCountry
											= quote.billingAddress().countryId;

                quote.billingAddress.subscribe(self.scBillingAddrChange, this, 'change');
                quote.totals.subscribe(self.scTotalsChange, this, 'change');
                quote.paymentMethod.subscribe(self.changePaymentProvider, this, 'change');
				
                return self;
            },
            
            context: function() {
                return self;
            },

            isShowLegend: function() {
                return true;
            },

            getCode: function() {
                return 'nuvei';
            },

			getNuveiIconUrl: function() {
				return window.checkoutConfig.payment[self.getCode()].checkoutLogoUrl;
			},

            getPaymentApmUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].paymentApmUrl;
            },
			
			getUpdateQuotePM: function() {
                return window.checkoutConfig.payment[self.getCode()].updateQuotePM;
            },
			
			getSessionToken: function() {
				var tmpCheckout = window.checkout;
				
//				$.getScript('https://cdn.safecharge.com/safecharge_resources/v1/checkout/checkout.js')
//					.done(function() {
//						window.nuveiCheckoutSdk = checkout;
//						window.checkout = tmpCheckout;
				
						console.log('getSessionToken() calls nuveiGetSessionToken()')
						nuveiGetSessionToken();
//					});
				
			},
			
			showGeneralError: function(msg) {
				jQuery('#nuvei_general_error .message div').html(jQuery.mage.__(msg));
				jQuery('#nuvei_general_error').show();
				document.getElementById("nuvei_general_error").scrollIntoView();
			},
			
			scBillingAddrChange: function() {
				self.writeLog('scBillingAddrChange()');
				
				if(quote.billingAddress() == null) {
					self.writeLog('scBillingAddrChange() - the BillingAddr is null. Stop here.');
					return;
				}
				
				if(quote.billingAddress().countryId == self.nuveiBillingCountry) {
					self.writeLog('scBillingAddrChange() - the country is same. Stop here.');
					return;
				}
				
				self.writeLog('scBillingAddrChange() - the country was changed to', quote.billingAddress().countryId);
				self.nuveiBillingCountry = nuveiBillingCountry = quote.billingAddress().countryId;
				
				// TODO reload the checkout
			},
			
			scTotalsChange: function() {
				self.writeLog('scTotalsChange()');
				
				var currentTotal = parseFloat(quote.totals().base_grand_total).toFixed(2);
				
				if(currentTotal == self.nuveiOrderTotal) {
					self.writeLog('scTotalsChange() - the total is same. Stop here.');
					return;
				}
				
				self.writeLog('scTotalsChange() - the total was changed to', currentTotal);
				self.nuveiOrderTotal = nuveiOrderTotal = currentTotal;
				
				// TODO reload the checkout
			},
			
			changePaymentProvider: function() {
				console.log('changePaymentProvider', quote.paymentMethod._latestValue.method)
				
				if(quote.paymentMethod._latestValue != null
					&& self.nuveiSelectedProvider != quote.paymentMethod._latestValue.method
				) {
					self.nuveiSelectedProvider = nuveiSelectedProvider = quote.paymentMethod._latestValue.method;
					self.scUpdateQuotePM();
				}
			},
			
			scUpdateQuotePM: function() {
				self.writeLog('scUpdateQuotePM()', self.nuveiSelectedProvider);
				
				// update new payment method
				if('' != self.nuveiSelectedProvider) {
					// prevent submiting form when click on Nuvei Checkout Pay button
//					if(self.getCode() == self.nuveiSelectedProvider) {
//						$('#co-payment-form').attr('onsubmit', 'return false;')
//					}
//					else {
//						$('#co-payment-form').attr('onsubmit', '')
//					}
					
					var scAjaxQuoteUpdateParams = {
						dataType	: "json",
						url			: self.getUpdateQuotePM(),
						cache		: false,
						showLoader	: true,
						data		: { paymentMethod: self.nuveiSelectedProvider }
					};

					$.ajax(scAjaxQuoteUpdateParams)
						.success(function(resp) {
//							nuveiGetSessionToken();
						})
						.error(function(e) {
							self.writeLog(e.responseText, null, 'error');
						});
				}
			},
			
			/**
			 * Help function to show some logs in Sandbox
			 * 
			 * @param string _text text to print
			 * @param mixed _param parameter to print
			 * @param string _mode show log or error
			 * 
			 * @returns void
			 */
			writeLog: function(_text, _param = null, _mode = 'log') {
				if(window.checkoutConfig.payment[self.getCode()].isTestMode !== true) {
					return;
				}
				
				if('log' == _mode) {
					if(null === _param) {
						console.log(_text);
					}
					else {
						console.log(_text, _param);
					}
				}
				else if('error' == _mode) {
					if(null === _param) {
						console.error(_text);
					}
					else {
						console.error(_text, _param);
					}
				}
			}
			
        });
    }
);
