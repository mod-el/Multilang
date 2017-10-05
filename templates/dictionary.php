<script type="text/javascript">
	window.editWord = function(field, k, l){
		field.style.background = '#DDD';
		ajax('<?=$this->getUrl()?>', '', 'k='+encodeURIComponent(k)+'&l='+encodeURIComponent(l)+'&v='+encodeURIComponent(field.getValue())+'&c_id='+c_id).then(function(r){
			field.style.background = '#FFF';
			if(r!=='ok')
				alert(r);
		});
	};

	window.newWord = function(){
		var words = {};
		_('#new-word').querySelectorAll('input').forEach(function(el){
			if(el.getAttribute('data-lang'))
			    words[el.getAttribute('data-lang')] = el.getValue();
        });

		return ajax(adminPrefix+'model-dictionary', '', 'c_id='+c_id+'&k='+encodeURIComponent(_('#new-word-k').getValue())+'&words='+encodeURIComponent(JSON.stringify(words))).then(function(){
			return loadAdminPage(['model-dictionary']);
        });
	};
</script>

<div style="padding: 0 20px">
    <h1>Dizionario</h1>

    <table style="width: 100%">
        <tr>
            <td>
                <b>Label</b>
            </td>
			<?php
			foreach($this->model->_Multilang->langs as $l){
				?>
                <td><b><?=strtoupper($l)?></b></td>
				<?php
			}
			?>
            <td></td>
        </tr>
        <tr id="new-word">
            <td>
                <input type="text" id="new-word-k" />
            </td>
			<?php
			foreach($this->model->_Multilang->langs as $l){
				?>
                <td>
                    <input type="text" data-lang="<?=$l?>" value="" />
                </td>
				<?php
			}
			?>
            <td><input type="button" value="Insert" onclick="newWord();"> </td>
        </tr>
		<?php
		$rows = $this->model->_Db->select_all('zk_dictionary', [], ['order_by'=>'k']);
		$dic = array();
		foreach($rows as $r)
			$dic[$r['k']][$r['lang']] = $r;

		foreach($dic as $k=>$langs){
			?>
            <tr>
                <td><b><?=entities($k)?></b></td>
				<?php
				foreach($this->model->_Multilang->langs as $l){
					?>
                    <td>
                        <input type="text" name="<?=entities($k)?>-<?=entities($l)?>" value="<?=isset($langs[$l]) ? entities($langs[$l]['v']) : ''?>" onchange="editWord(this, '<?=entities($k)?>', '<?=entities($l)?>')" />
                    </td>
					<?php
				}
				?>
                <td></td>
            </tr>
			<?php
		}
		?>
    </table>
</div>