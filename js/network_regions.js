jQuery.noConflict();

(function($){
	
	$(function($) {
		
		var $button = $('a#add-region-button'),
			$fields = $('.group-field'),
			$template = $fields.eq(0),
			$count = $fields.length,
			deleted = 0;
			$flash = $('div#message');
		
		$button.on('click', function(evt){
			evt.preventDefault();
			
			$newField = copyField($template, $count);
			
			$('#fieldlist').append($newField);
			
			$count++;
		});
		
		addDelete($fields);
		
		$('#fieldlist').on('click', 'a.delete-region', function(evt){
			evt.preventDefault();
			
			var value = $(this).siblings('input').val();
			
			var	$removal = $('<input>').attr({
					type: 'hidden',
					name: 'groups_for_removal['+deleted+']'
				}).val(value);
			
			$(this).parent().remove();
			
			$('#fieldlist').append($removal);
			
			if($('.group-field').length < 1)
				$button.trigger('click');
			
			deleted++;
		});
		
		if($flash.length > 0)
		{
			setTimeout(function(){
				$flash.fadeOut('slow');
			}, 4000);
		}
	});
	
	function copyField($temp, $index)
	{
		var $element = $('<div>').attr('class', 'field-box'),
			$copy = $temp.clone(),
			name = 'option[blog_group]['+$index+'_new]',
			$label = $('<label>').attr('for', name).html('Group');
		
		$copy.attr('name', name);
		$copy.val('');
		
		$element.append($label).append($copy);
		addDelete($copy);

		return $element;
	}
	
	function addDelete($items)
	{
		var $deleteBtn = $('<a>').attr({'class':'delete-region', 'href':'#'}).html('delete');
		$items.parent().append($deleteBtn);
	}
	
})( jQuery );
