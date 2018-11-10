<script type="text/javascript">
	window.editWord = function (field, section, word, l) {
		field.style.background = '#DDD';
		ajax('<?=$this->getUrl()?>', {}, {
			'c_id': c_id,
			'section': section,
			'word': word,
			'l': l,
			'v': field.getValue(true)
		}).then(r => {
			field.style.background = '#FFF';
			if (r !== 'ok')
				alert(r);
		});
	};

	window.newWord = function (section) {
		let row = _('#new-word-row-' + section);
		if (!row)
			return;

		var words = {};
		row.querySelectorAll('input').forEach(el => {
			if (el.getAttribute('data-lang'))
				words[el.getAttribute('data-lang')] = el.getValue(true);
		});

		return ajax(adminPrefix + 'model-dictionary', {}, {
			'c_id': c_id,
			'section': section,
			'word': row.querySelector('[data-word]').getValue(true),
			'words': JSON.stringify(words)
		}).then(r => {
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
		if (!confirm('<?= entities($this->word('multilang.delete_confirmation')) ?>'))
			return false;

		return ajax(adminPrefix + 'model-dictionary', {}, {
			'c_id': c_id,
			'section': section,
			'delete': word
		}).then(r => {
			if (r === 'ok')
				return loadAdminPage(['model-dictionary']);
			else
				alert(r);
		});
	};

	window.changeAdminLang = function (lang) {
		return ajax(adminPrefix + 'model-dictionary/changeAdminLang', {'ajax': ''}, {
			'c_id': c_id,
			'lang': lang
		}).then(r => {
			if (r !== 'ok')
				alert(r);
			document.location.reload();
		});
	};
</script>

<style>
	h2 a:link, h2 a:visited {
		color: #333;
	}

	h2 .access-level {
		color: #999;
		font-weight: normal;
		font-size: 12px;
	}

	.lang-section {
		width: 100%;
		padding-left: 25px;
		padding-bottom: 20px;
		-webkit-box-sizing: border-box;
		-moz-box-sizing: border-box;
		box-sizing: border-box;
	}

	.lang-section table {
		width: 100%;
	}
</style>

<div style="padding: 0 20px">
	<div class="float-right">
		<?= entities($this->word('multilang.admin-lang')) ?>
		<select onchange="this.getValue().then(l => changeAdminLang(l))">
			<option value=""></option>
			<?php
			foreach ($this->model->_Multilang->langs as $l) {
				?>
				<option value="<?= entities($l) ?>"<?= $l == $this->model->_Multilang->lang ? ' selected' : '' ?>><?= entities(ucwords($l)) ?></option>
				<?php
			}
			?>
		</select>
	</div>
	<h1><?= entities($this->word('multilang.dictionary')) ?></h1>

	<?php
	$dictionary = $this->model->_Multilang->getDictionary();

	foreach ($dictionary as $sectionIdx => $section) {
		if (!$this->model->_Multilang->isUserAuthorized($sectionIdx))
			continue;
		?>
		<form action="?" method="post" id="new-word-<?= $sectionIdx ?>" onsubmit="newWord('<?= entities($sectionIdx) ?>'); return false"></form>
		<h2>
			<a href="#" onclick="showOrHideLangSection('<?= entities($sectionIdx) ?>'); return false"><?= entities($sectionIdx) ?></a>
			<span class="access-level">[<?= entities($section['accessLevel']) ?>]</span>
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
				<tr id="new-word-row-<?= entities($sectionIdx) ?>">
					<td></td>
					<td>
						<input type="text" form="new-word-<?= $sectionIdx ?>" placeholder="<?= entities($this->word('multilang.new')) ?>" data-word/>
					</td>
					<?php
					foreach ($this->model->_Multilang->langs as $l) {
						?>
						<td>
							<input type="text" form="new-word-<?= $sectionIdx ?>" data-lang="<?= $l ?>" value=""/>
						</td>
						<?php
					}
					?>
					<td>
						<input type="submit" form="new-word-<?= $sectionIdx ?>" value="<?= entities($this->word('multilang.insert')) ?>"/>
					</td>
				</tr>
				<?php
				ksort($section['words']);
				foreach ($section['words'] as $word => $langs) {
					?>
					<tr>
						<td>[<a href="#"
								onclick="deleteWord('<?= urlencode($sectionIdx) ?>', '<?= urlencode($word) ?>'); return false"> x </a>]
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
			</table>
		</div>
		<?php
	}
	?>
</div>