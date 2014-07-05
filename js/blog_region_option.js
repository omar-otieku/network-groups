jQuery.noConflict();

(function($){
		var $select = $('select[name="option[blog_group]"]');
		$select.detach();
		
	$(function($) {
		var $row = $('<tr>').html('<th>Blog Group</th>').append($('<td>').append($select));
		$('table.form-table tbody').append($row);
		$select.show();
	});
	
})( jQuery );
