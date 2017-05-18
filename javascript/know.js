jQuery(document).ready(function($) {
	$('#autocomplete').autocomplete({
		serviceUrl: 'autocomplete.php',
		minChars: 2,
		onSelect: function(suggestion) {
			$('#q').val(suggestion.data);
		},
	});
	
	$('#autocomplete').focus();
	
	$('#artist').submit(function() {
		if (!$('#q').val()) {
			$('#q').val($('#autocomplete').val());
		}
		$('#artist').attr('action', encodeURIComponent($('#q').val()).replace(/%20/g, "+"));
	});
	
});

function $c(msg) {
	console.log(msg);
}