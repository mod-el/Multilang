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

	window.showOrHideLangSection = function (sectionIdx) {
		let section = _('section-' + sectionIdx);
		if (section) {
			if (section.style.display === 'none')
				section.style.display = 'block';
			else
				section.style.display = 'none';
		}
	};

	window.deleteWord = function (section, word) {
        if(!confirm('<?= entities($this->word('multilang.delete_confirmation')) ?>'))
        	return false;

		return ajax(adminPrefix + 'model-dictionary', '', 'c_id=' + c_id + '&section=' + encodeURIComponent(section) + '&delete=' + encodeURIComponent(word)).then((r) => {
			if (r === 'ok')
				return loadAdminPage(['model-dictionary']);
			else
				alert(r);
		});
	};
</script>

<style>
    h2 a:link, h2 a:visited {
        color: #333;
    }

    .lang-section {
        width: 100%;
        padding-left: 25px;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
    }

    .lang-section table {
        width: 100%;
    }
</style>

<div style="padding: 0 20px">
    <h1><?= entities($this->word('multilang.dictionary')) ?></h1>

	<?php
	$dictionary = $this->model->_Multilang->getDictionary();

	foreach ($dictionary as $sectionIdx => $section) {
		if (!$this->model->_Multilang->isUserAuthorized($sectionIdx))
			continue;
		?>
        <h2><a href="#"
               onclick="showOrHideLangSection('<?= entities($sectionIdx) ?>'); return false"><?= entities($sectionIdx) ?></a>
        </h2>
        <div class="lang-section" id="section-<?= entities($sectionIdx) ?>">
            <table>
                <tr>
                    <td></td>
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
                        <td>[<a href="#"
                                onclick="deleteWord('<?= urlencode($sectionIdx) ?>', '<?= urlencode($word) ?>'); return false">
                                x </a>]
                        </td>
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
            </table>
        </div>
		<?php
	}
	?>
</div>