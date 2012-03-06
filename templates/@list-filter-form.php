<?php
	if (($GLOBALS['page']['location'] == 'a') || ($GLOBALS['page']['location'] == 'aa'))
	{
		echo '<div class="full" id="filter_form_container" style="display: none;">';
		echo $form->renderPrototype(null);;
		echo '</div>';
		echo '<div class="quick">';
		$counter = 0;
		$quick = array();
		foreach ($form->i('_main')->elementsRecursive() as $element)
			if ($element->get('filter-quick') || ($element->field && $element->field->get('filter-quick')))
				$quick[] = $element;
		if ($quick)
		{
			foreach ($quick as $element)
			{
				$element = clone($element);
				if (($element->type() == 'checkbox') || ($element->type() == 'radio'))
					$element->attributeSet('onclick', 'document.getElementById(\''.$element->id.'\').checked = this.checked;');
				else
					$element->attributeSet('onchange', 'document.getElementById(\''.$element->id.'\').value = this.value;');
				$element->id .= '_filter_quick';
				echo $element->renderPrototype(null);
			}
			foreach (array('filter-submit', 'filter-clear') as $element)
			{
				$element = $form->i('_submit')->i($element);
				$element = clone($element);
				$element->attributeSet('onclick', 'document.getElementById(\''.$element->id.'\').click();');
				$element->id .= '_filter_quick';
				echo $element->renderPrototype(null);
			}
		}
		echo '&nbsp;<a href="#" onclick="$(\'#filter_form_container\').toggle();">' . __('search-full', '#core') . '</a>';
		echo '</div>';
	}
	else
	{
		echo '<div class="full" id="filter_form_container" style="display: none;">';
		echo '<a href="#" class="close" onclick="$(\'#filter_form_container\').hide(); return false;">' . __('search-close', '#core') . '</a>';
		echo $form->renderPrototype(null);;
		echo '</div>';
	}
?>