<script type="text/javascript">
	window.editWord = function (field, section, word, l) {
		field.style.background = '#DDD';
		ajax('<?=$this->getUrl()?>', '', 'section=' + encodeURIComponent(section) + '&word=' + encodeURIComponent(word) + '&l=' + encodeURIComponent(l) + '&v=' + encodeURIComponent(field.getValue(true)) + '&c_id=' + c_id).then(r => {
			field.style.background = '#FFF';
			if (r !== 'ok')
				alert(r);
		});
	};

	window.newWord = function (section) {
		let row = _('#new-word-' + section);
		if (!row)
			return;

		var words = {};
		row.querySelectorAll('input').forEach(el => {
			if (el.getAttribute('data-lang'))
				words[el.getAttribute('data-lang')] = el.getValue(true);
		});

		return ajax(adminPrefix + 'model-dictionary', '', 'c_id=' + c_id + '&section=' + encodeURIComponent(section) + '&word=' + encodeURIComponent(row.querySelector('[data-word]').getValue(true)) + '&words=' + encodeURIComponent(JSON.stringify(words))).then((r) => {
			if (r === 'ok')
				return loadAdminPage(['model-dictionary']);
			else
				alert(r);
		});
	};
</script>

<div style="padding: 0 20px">
    <h1><?= entities($this->word('multilang.dictionary')) ?></h1>

    <table style="width: 100%">
		<?php
		$dictionary = $this->model->_Multilang->getDictionary();

		foreach ($dictionary as $sectionIdx => $section) {
			if (!$this->model->_Multilang->isUserAuthorized($sectionIdx))
				continue;
			?>
            <tr>
                <td colspan="<?= (3 + count($this->model->_Multilang->langs)) ?>">
                    <h2><?= entities($sectionIdx) ?></h2>
                </td>
            </tr>
            <tr>
                <td style="width: 20px"></td>
                <td>
                    <b><?= entities($this->word('multilang.label')) ?></b>
                </td>
				<?php
				foreach ($this->model->_Multilang->langs as $l) {
					?>
                    <td><b><?= strtoupper($l) ?></b></td>
					<?php
				}
				?>
                <td></td>
            </tr>
			<?php
			foreach ($section['words'] as $word => $langs) {
				?>
                <tr>
                    <td></td>
                    <td><b><?= entities($word) ?></b></td>
					<?php
					foreach ($this->model->_Multilang->langs as $l) {
						?>
                        <td>
                            <input type="text" name="<?= entities($word) ?>-<?= entities($l) ?>"
                                   value="<?= entities($langs[$l] ?? '') ?>"
                                   onchange="editWord(this, '<?= entities($sectionIdx) ?>', '<?= entities($word) ?>', '<?= entities($l) ?>')"/>
                        </td>
						<?php
					}
					?>
                    <td></td>
                </tr>
				<?php
			}
			?>
            <tr id="new-word-<?= entities($sectionIdx) ?>">
                <td></td>
                <td>
                    <input type="text" data-word/>
                </td>
				<?php
				foreach ($this->model->_Multilang->langs as $l) {
					?>
                    <td>
                        <input type="text" data-lang="<?= $l ?>" value=""/>
                    </td>
					<?php
				}
				?>
                <td>
                    <input type="button" value="<?= entities($this->word('multilang.insert')) ?>"
                           onclick="newWord('<?= entities($sectionIdx) ?>')"/>
                </td>
            </tr>
			<?php
		}
		?>
    </table>
</div>