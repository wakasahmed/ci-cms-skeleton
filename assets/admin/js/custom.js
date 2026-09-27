function adminModal(modalId, action) {
	var modalElement = document.getElementById(modalId);
	if (!modalElement || !window.bootstrap) {
		return;
	}
	var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
	if (action === 'hide') {
		modal.hide();
	} else {
		modal.show();
	}
}

(function (window, document, $) {
	'use strict';

	var phoneInputs = window.AdminIntlPhoneInputs = window.AdminIntlPhoneInputs || {};

	function validationMessage(input, instance) {
		if (!input.value.trim()) {
			return 'Please enter a phone number.';
		}

		var validationError = window.intlTelInput && window.intlTelInput.VALIDATION_ERROR;
		var errorCode = instance.getValidationError();
		if (validationError) {
			if (errorCode === validationError.INVALID_COUNTRY_CODE) { return 'Select a valid country calling code.'; }
			if (errorCode === validationError.TOO_SHORT) { return 'The phone number is too short for the selected country.'; }
			if (errorCode === validationError.TOO_LONG) { return 'The phone number is too long for the selected country.'; }
		}

		return 'Enter a valid phone number for the selected country.';
	}

	function phoneEntry(element) {
		return element && element.id ? phoneInputs[element.id] : null;
	}

	if ($ && $.validator) {
		$.validator.addMethod('intlPhone', function (value, element) {
			if (this.optional(element)) { return true; }

			var entry = phoneEntry(element);
			if (!entry || !entry.ready) {
				element.setAttribute('data-intl-phone-error', 'Phone validation is loading. Please try again.');
				return false;
			}

			var isValid = entry.instance.isValidNumber();
			element.setAttribute('data-intl-phone-error', isValid ? '' : validationMessage(element, entry.instance));
			return isValid;
		}, function (params, element) {
			return element.getAttribute('data-intl-phone-error') || 'Enter a valid phone number for the selected country.';
		});
	}

	function initializeInternationalPhones() {
		if (typeof window.intlTelInput !== 'function') { return; }

		document.querySelectorAll('input.intl-phone[id]').forEach(function (candidate) {
			var inputId = candidate.id;
			var input = document.getElementById(inputId);
			if (!input || input !== candidate || phoneInputs[inputId]) { return; }

			var instance = window.intlTelInput(input, {
				initialCountry: (input.getAttribute('data-initial-country') || 'sa').toLowerCase(),
				separateDialCode: true,
				strictMode: true,
				loadUtils: function () {
					return import((window.AdminConfig || {}).intlTelUtilsUrl);
				}
			});
			var entry = phoneInputs[inputId] = { input: input, instance: instance, ready: false };

			Promise.resolve(instance.promise).then(function () {
				entry.ready = true;
				input.removeAttribute('data-intl-phone-error');
			}).catch(function () {
				input.setAttribute('data-intl-phone-error', 'Phone validation could not be loaded. Please refresh the page.');
			});

			function revalidate() {
				if ($ && $.fn.validate && ($(input).attr('aria-invalid') === 'true' || document.activeElement !== input)) {
					$(input).valid();
				}
			}

			input.addEventListener('blur', revalidate);
			input.addEventListener('input', function () {
				if ($(input).attr('aria-invalid') === 'true') { revalidate(); }
			});
			input.addEventListener('countrychange', revalidate);

			if (input.form) {
				input.form.addEventListener('submit', function () {
					if (entry.ready && instance.isValidNumber()) {
						input.value = instance.getNumber();
					}
				}, true);
			}
		});
	}

	document.addEventListener('DOMContentLoaded', initializeInternationalPhones);
}(window, document, window.jQuery));

jQuery(document).ready(function ($) {
	$(".pmask").mask("+000-00-000-0000");

	function showImageRemovalToast(icon, title) {
		if (!window.Swal) { return; }
		window.Swal.fire({
			icon: icon,
			title: title,
			toast: true,
			position: 'top-end',
			showConfirmButton: false,
			timer: 2500,
			timerProgressBar: true
		});
	}

	function clearFileUploadPreview(button) {
		var preview = button.closest('.admin-current-file-remove').prev().find('[data-file-upload-preview]').first();
		if (!preview.length) { return; }
		preview.empty().append('<span class="admin-file-upload-empty" data-file-upload-empty><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i><span>No file selected</span></span>');
	}

	function performImageRemoval(delLink, button) {
		$.getJSON(delLink)
			.done(function (response) {
				if (response && response.success) {
					clearFileUploadPreview(button);
					button.closest('.admin-current-file-remove').remove();
					showImageRemovalToast('success', 'File removed successfully.');
				}
				else {
					showImageRemovalToast('error', (response && response.message) ? response.message : 'Unable to remove the file.');
				}
			})
			.fail(function () {
				showImageRemovalToast('error', 'Unable to remove the file.');
			});
	}

	var pendingImageRemoval = null;

	$(".removeFile").on('click',function(){

		var button = $(this);
		var recordID = button.attr('data-file-id');
		var controller = button.attr('data-controller');
		var fileKey = button.attr('data-file-name');

		recordID = recordID.replace("img","");

		pendingImageRemoval = {
			delLink: admin_url+controller+'/removefile/'+recordID+'/'+fileKey,
			button: button
		};

		adminModal('removeImageModal', 'show');

	});

	$("#removeImageLink").on('click', function () {
		if (!pendingImageRemoval) { return; }
		var action = pendingImageRemoval;
		pendingImageRemoval = null;
		adminModal('removeImageModal', 'hide');
		performImageRemoval(action.delLink, action.button);
	});
	 $("#copy_link").on('click',function(){
	    $("#payment_link").select();
		var successful = document.execCommand('copy');  
		if(successful)
		{
			alert("Payment Link Copied Successfully");
		}
		else{
			alert("Unable to support your browser, please manually copy the link");	
		}
  });
	$("#blog_category, #blog_tags").select2();
	//For hiding the alert div
	$(".alertBox").on('click', function () {
		$(this).closest('.alertrow').fadeOut(200, function () {
			$(this).remove();	
		});
	});
	$('.aplha').keyup(function (event) {
	if(event.which == 37 || event.which == 38 || event.which == 39 || event.which == 40 || event.which == 8 || event.which == 46) { // Left / Up / Right / Down Arrow, Backspace, Delete keys
			 return;
	} 
     this.value = this.value.replace(/[^a-zA-Z0-9\- ]/g,'');
	});
	$('.uname').keyup(function (event) { 
	if(event.which == 37 || event.which == 38 || event.which == 39 || event.which == 40 || event.which == 8 || event.which == 46) { // Left / Up / Right / Down Arrow, Backspace, Delete keys
			 return;
	}
     this.value = this.value.replace(/[^a-z0-9\-\.\_]/g,'');
	});
	
	$('.dec').keyup(function (event) { 
	if(event.which == 37 || event.which == 38 || event.which == 39 || event.which == 40 || event.which == 8 || event.which == 46) { // Left / Up / Right / Down Arrow, Backspace, Delete keys
			 return;
	}
     this.value = this.value.replace(/[^0-9\.]/g,'');
	});
	$('.numb').keyup(function (event) {
		 if(event.which == 37 || event.which == 38 || event.which == 39 || event.which == 40 || event.which == 8 || event.which == 46) { // Left / Up / Right / Down Arrow, Backspace, Delete keys
			 return;
		 }
		 
     this.value = this.value.replace(/[^0-9]/g,'');
	});
	$('.aplhasmall').keyup(function (event ) {
		
		 if(event.which == 37 || event.which == 38 || event.which == 39 || event.which == 40 || event.which == 8 || event.which == 46) { // Left / Up / Right / Down Arrow, Backspace, Delete keys
			 return;
		 }
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen 
     	this.value = input;
	});
	
	$("#brand_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#brand_slug").val(input);
	});
	$("#archi_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#archi_slug").val(input);
	});
	$("#prod_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#prod_slug").val(input);
	});
	$("#tag_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#tag_slug").val(input);
	});
	
	function generateSlug(value, language) {
		var input = String(value || '').trim();
		if (typeof input.normalize === 'function') {
			input = input.normalize('NFKD');
		}

		// Remove Latin combining marks, Arabic diacritics, and tatweel.
		input = input
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[\u0610-\u061a\u0640\u064b-\u065f\u0670\u06d6-\u06ed]/g, '')
			.toLocaleLowerCase(language === 'ar' ? 'ar' : 'en');

		var unsupportedCharacters;
		try {
			unsupportedCharacters = new RegExp('[^\\p{L}\\p{N}]+', 'gu');
		} catch (error) {
			unsupportedCharacters = /[^a-z0-9\u0600-\u06ff\u0750-\u077f\u08a0-\u08ff]+/gi;
		}

		return input
			.replace(unsupportedCharacters, '-')
			.replace(/-+/g, '-')
			.replace(/^-|-$/g, '');
	}

	$(document).on('click', '.generate-slug', function () {
		var button = $(this);
		var source = $(button.data('slug-source'));
		var target = $(button.data('slug-target'));
		var slug = generateSlug(source.val(), button.data('slug-language'));

		target.val(slug).trigger('change').focus();
	});

	$(document).on('keyup', '.auto-slug-source[data-slug-target]', function () {
		var source = $(this);
		var target = $(source.data('slug-target'));
		if (!target.length) return;

		target.val(generateSlug(this.value, source.data('slug-language'))).trigger('change');
	});

	$("#area_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~!\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#area_slug").val(input);
	});
	$("#hotel_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~!\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#hotel_slug").val(input);
	});
	
	
	$("#proj_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~!\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#proj_slug").val(input);
	});
	$("#prop_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~!\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#prop_slug").val(input);
	});	
	
	$(".slug").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$(this).val(input);
	});
	
	
	$("#blog_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~!\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#blog_slug").val(input);
	});
	$("#cat_name").on('keyup',function(){
		var input = this.value;
		input = input.replace(/^\s\s*/, '') // Trim start
        .replace(/\s\s*$/, '') // Trim end
        .toLowerCase() // Camel case is bad
        .replace(/[^a-z0-9_\-~\+\s]+/g, '') // Exchange invalid chars
        .replace(/[\s]+/g, '-'); // Swap whitespace for single hyphen
		
		$("#cat_slug").val(input);
	});
	
	
	//For launching the delete confirmation modal
	$(".delitem").on('click',function(){
		var recordID = this.id;
		var controller = $(this).attr('data-controller');
		var recordName = typeof $(this).attr('data-record-name') !== 'undefined' ? $(this).attr('data-record-name') : '';
		recordID = recordID.replace("recordID","");
		var delLink = admin_url+controller+'/delete/'+recordID;
		$("#deleteLink").attr("onclick","window.location='"+delLink+"'");
		console.log(recordName);
		if(recordName != "")
		{
			$("#deleteModalLabel").html(recordName);
		}
		adminModal('deleteModal', 'show');
	});
	
	$(".changestatus").on('click',function(){
		var recordID = this.id;
		var controller = $(this).attr('data-controller');
		
		if(controller=="pages" || controller=="blogs" || controller=="activities"  || controller=="events")
		{
			var status = ($(this).html() == "Published") ? "Un-Published" : "Published";
		}
		else{
			var status = ($(this).html() == "Enable") ? "Disable" : "Enable";
		}
		$("#modStatus").html(status);
		recordID = recordID.replace("statusID","");
		var delLink = admin_url+controller+'/changestatus/'+recordID+'/'+status;
		$("#statusLink").attr("data-status-url",delLink);
		adminModal('statusModal', 'show');
	});
	var statusReq = null;
	$("#statusLink").on('click',function(){
		adminModal('statusModal', 'hide');
		var statusLink = $(this).attr('data-status-url');
		if(statusReq!=null)
		{
			statusReq.abort();
		}
		statusReq = $.getJSON(statusLink,function(d){
			if(d.status=="true")
			{
				$("#statusID"+d.id).html(d.currentStatus);	
			}
		});
		
	});
	//For launching the duplicate property modal
	$(".dupitem").on('click',function(){
		var recordID = this.id;
		var controller = $(this).data('controller');
		var recname = $(this).attr('data-rec-name');
		$("#recname").html(recname);
		recordID = recordID.replace("copyID","");
		var delLink = admin_url+controller+'/duplicate/'+recordID;
		$("#duplicateLink").attr("onclick","window.location='"+delLink+"'");
		adminModal('dupModal', 'show');
	});
	
	//Admin Settings Javascript START
	$("#adm_setting").validate({
		rules:{
			admin_name:{
				required:true
			},
			admin_email:{
				required:true,
				email: true
			},
			
			
			admin_current_pwd:{
				required: function(){
                        return $("#admin_current_pwd").val() != "";
                  },
				minlength:6,
				maxlength:20
			},
			
			admin_new_pwd:{
					required: function(){
                        return $("#admin_current_pwd").val() != "";
                  },
				minlength:6,
				maxlength:20
			},
			admin_new_pwd2:{
				required: function(){
                        return $("#admin_current_pwd").val() != "";
                  },
				minlength:6,
				maxlength:20,
				equalTo:"#admin_new_pwd"
			}
			
		},
		messages: {
			admin_new_pwd2: {
			equalTo: "Password didn't matched with new password"
			},
			
		},
		errorClass: "validate-has-error",
		errorElement: "span",
		highlight:function(element, errorClass, validClass) {
			$(element).addClass('is-invalid').attr('aria-invalid', 'true').closest('.admin-field').addClass('validate-has-error');
		},
		unhighlight: function(element, errorClass, validClass) {
			$(element).removeClass('is-invalid').removeAttr('aria-invalid').closest('.admin-field').removeClass('validate-has-error');
		}
	});
	
	// Keep the select-all control in sync with every deletable record checkbox.
	var $selectAllCheckbox = $("#all-checkbox");
	var $recordCheckboxes = $("#multiDel").find(".cselect");

	function updateSelectAllCheckbox() {
		var selectedCount = $recordCheckboxes.filter(":checked").length;
		var recordCount = $recordCheckboxes.length;

		$selectAllCheckbox
			.prop("checked", recordCount > 0 && selectedCount === recordCount)
			.prop("indeterminate", selectedCount > 0 && selectedCount < recordCount)
			.prop("disabled", recordCount === 0);
	}

	$selectAllCheckbox.on("change", function() {
		$recordCheckboxes
			.prop("checked", this.checked)
			.trigger("change");
	});

	$recordCheckboxes.on("change", updateSelectAllCheckbox);
	updateSelectAllCheckbox();
	
	$("#deleteAllRecords").on('click',function(){
		var r = 0;
		$(".cselect").each(function() {
			if(this.checked==true)
			{
			r = 1;
			}
		});
			if(r==1)
			{
			adminModal('delAllModal', 'show');
			}
			else{
				alert("Please select one or more records to delete");	
			}
	});
	
	$("#delAllSubmit").on('click',function(){
		$("#multiDel").submit();	
	});
	$("#per_page").on('change',function(){
		
		window.location = controller+'?per_page='+this.value;	
	
	});
	
	//Add User Validation
	$("#admin_form").validate({
		rules:{
			full_name:{
				required:true
			},
			email:{
				required:true,
				email: true
			},
			phone:{
				intlPhone: true
			},
			user_name:{
				required: true,
				minlength:3,
				maxlength:20
			},
			pwd:{
				required: true,
				minlength:6,
				
			},
			pwd2:{
				required: true,
				minlength:6,
				
				equalTo:"#pwd"
			}
			
		},
		messages: {
			admin_new_pwd2: {
			equalTo: "Password didn't matched with new password"
			},
			
		},
		
		errorClass: "validate-has-error",
		errorElement: "span",
		highlight:function(element, errorClass, validClass) {
			$(element).addClass('is-invalid').attr('aria-invalid', 'true').closest('.admin-field').addClass('validate-has-error');
		},
		unhighlight: function(element, errorClass, validClass) {
			$(element).removeClass('is-invalid').removeAttr('aria-invalid').closest('.admin-field').removeClass('validate-has-error');
		}
	});
	var req = null;
	var categorySlugWasEdited = false;
	function normalizeCategorySlug(value) {
		return $.trim(value || '').toLowerCase().replace(/[\s_]+/g, '-').replace(/[^\p{L}\p{N}-]+/gu, '').replace(/-+/g, '-').replace(/^-|-$/g, '');
	}
	function showCategoryError(message) {
		$('#catErrorMsg').text(message || '').toggleClass('d-none', !message);
	}
	function resetCategoryModal() {
		categorySlugWasEdited = false;
		$('#blog_cat_ajax, #blog_cat_slug_ajax').val('').removeClass('is-invalid').removeAttr('aria-invalid').closest('.admin-field').removeClass('validate-has-error');
		showCategoryError('');
	}
	$('#addNewCat').on('click', function() {
		resetCategoryModal();
		adminModal('addBlogCatModal', 'show');
		setTimeout(function() { $('#blog_cat_ajax').trigger('focus'); }, 180);
	});
	$('#blog_cat_ajax').on('input', function() {
		if (!categorySlugWasEdited) $('#blog_cat_slug_ajax').val(normalizeCategorySlug(this.value));
	});
	$('#blog_cat_slug_ajax').on('input', function() {
		categorySlugWasEdited = true;
		this.value = normalizeCategorySlug(this.value);
	});
	$('#bCatSubmit').on('click', function() {
		var name = $.trim($('#blog_cat_ajax').val());
		var slug = normalizeCategorySlug($('#blog_cat_slug_ajax').val());
		var $button = $(this);
		$('#blog_cat_slug_ajax').val(slug);
		$('#blog_cat_ajax, #blog_cat_slug_ajax').removeClass('is-invalid').removeAttr('aria-invalid').closest('.admin-field').removeClass('validate-has-error');
		showCategoryError('');
		if (!name || !slug) {
			var $missing = !name ? $('#blog_cat_ajax') : $('#blog_cat_slug_ajax');
			$missing.addClass('is-invalid').attr('aria-invalid', 'true').closest('.admin-field').addClass('validate-has-error');
			showCategoryError('Enter both the category name and URL slug.');
			$missing.trigger('focus');
			return;
		}
		var existingValue = null;
		$('#blog_category option').each(function() {
			var optionName = $.trim($(this).text().replace(/\s+\(Disabled\)$/, '')).toLowerCase();
			if (optionName === name.toLowerCase()) { existingValue = String(this.value); return false; }
		});
		if (existingValue !== null) {
			var selected = ($('#blog_category').val() || []).map(String);
			if (selected.indexOf(existingValue) === -1) selected.push(existingValue);
			$('#blog_category').val(selected).trigger('change');
			adminModal('addBlogCatModal', 'hide');
			return;
		}
		var requestData = { cat_name: name, cat_slug: slug };
		if (window.AdminConfig && AdminConfig.csrfName && AdminConfig.csrfHash) requestData[AdminConfig.csrfName] = AdminConfig.csrfHash;
		$('#ajax_loader_cat').removeClass('d-none');
		$button.prop('disabled', true).attr('aria-disabled', 'true');
		if (req !== null) req.abort();
		req = $.ajax({
			url: admin_url + 'blog-categories/addRecordAJAX', type: 'POST', data: requestData, dataType: 'json'
		}).done(function(data) {
			if (data.status !== 'true') { showCategoryError(data.message || 'The category could not be added.'); return; }
			if (data.csrf_name && data.csrf_hash && window.AdminConfig) { AdminConfig.csrfName = data.csrf_name; AdminConfig.csrfHash = data.csrf_hash; }
			$('#blog_category').append(new Option(data.cat_name, data.cat_id, true, true)).trigger('change');
			adminModal('addBlogCatModal', 'hide');
			resetCategoryModal();
		}).fail(function(xhr) {
			var response = xhr.responseJSON || {};
			showCategoryError(response.message || 'The category could not be added. Please try again.');
		}).always(function() {
			req = null;
			$('#ajax_loader_cat').addClass('d-none');
			$button.prop('disabled', false).removeAttr('aria-disabled');
		});
	});
	$('#blog_cat_ajax, #blog_cat_slug_ajax').on('keydown', function(event) {
		if (event.key === 'Enter') { event.preventDefault(); $('#bCatSubmit').trigger('click'); }
	});

	//For removing the section image
	$(".removeImage").on('click',function(){
		var recordID = this.id;
		var controller = $(this).attr('data-controller');
		recordID = recordID.replace("img","");
		var delLink = admin_url+controller+'/removeimage/'+recordID;
		$.getJSON(delLink);
		$("#image"+recordID).remove();
	});	
	$(".removeImage2").on('click',function(){
		var recordID = this.id;
		var controller = $(this).attr('data-controller');
		recordID = recordID.replace("img","");
		var delLink = admin_url+controller+'/removeimage2/'+recordID;
		$.getJSON(delLink);
		$("#iimage"+recordID).remove();
	});
	
	//For removing the portfolio image
	$(".removePImage").bind('click',function(){
		alert("Test");
		var recordID = this.id;
		alert(recordID);
		recordID = recordID.replace("pimg","");
		$("#imgContainer"+recordID).remove();
	});	
	
	$(document).on('change','#banner_type',function(){

		if($(this).val() == 1){
			$('.c_editor').show();
			$('.block_n').hide();
		}else{
			$('.c_editor').hide();
			$('.block_n').show();

		}

	});
	
});//End of document ready

$.fn.hasExtension = function(exts) {
    return (new RegExp('(' + exts.join('|').replace(/\./g, '\\.') + ')$')).test($(this).val());
}
function validateURL(value) {
    return /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value);
}
