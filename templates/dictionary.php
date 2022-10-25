<script type="text/javascript">
	window.editWord = async function (field, section, word, l) {
		field.style.background = '#DDD';
		return adminApiRequest('page/model-dictionary/edit-word', {
			section,
			word,
			l,
			v: await field.getValue()
		}).then(response => {
			if (!response.success)
				throw 'Errore risposta';
		}).catch(err => {
			reportAdminError(err);
		}).finally(() => {
			field.style.background = '#FFF';
		});
	};

	window.newWord = async function (section) {
		let row = _('#new-word-row-' + section);
		if (!row)
			return;

		document.body.style.cursor = 'loading';

		let words = {};
		row.querySelectorAll('input').forEach(el => {
			if (el.getAttribute('data-lang'))
				words[el.getAttribute('data-lang')] = el.getValue(true);
		});

		return adminApiRequest('page/model-dictionary/new-word', {
			section,
			word: row.querySelector('[data-word]').getValue(true),
			words
		}).then(response => {
			if (!response.success)
				throw 'Errore risposta';

			return loadAdminPage('model-dictionary');
		}).catch(err => {
			reportAdminError(err);
			document.body.style.cursor = 'auto';
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

	window.deleteWord = async function (section, word) {
		if (!confirm('<?= $this->word('multilang.delete_confirmation') ?>'))
			return;

		document.body.style.cursor = 'loading';

		return adminApiRequest('page/model-dictionary/delete-word', {
			section,
			word
		}).then(response => {
			if (!response.success)
				throw 'Errore risposta';

			return loadAdminPage('model-dictionary');
		}).catch(err => {
			reportAdminError(err);
			document.body.style.cursor = 'auto';
		});
	};

	window.changeAdminLang = async function (lang) {
		return adminApiRequest('page/model-dictionary/change-admin-lang', {lang}).then(response => {
			if (!response.success)
				throw 'Errore risposta';

			document.location.reload();
		}).catch(err => {
			reportAdminError(err);
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
		<?= $this->word('multilang.admin-lang') ?>
		<select onchange="this.getValue().then(l => changeAdminLang(l))">
			<option value=""></option>
			<?php
			foreach (\Model\Multilang\Ml::getLangs() as $l) {
				?>
				<option value="<?= entities($l) ?>"<?= $l == \Model\Multilang\Ml::getLang() ? ' selected' : '' ?>><?= entities(ucwords($l)) ?></option>
				<?php
			}
			?>
		</select>
	</div>
	<h1><?= $this->word('multilang.dictionary') ?></h1>

	<?php
	$dictionary = \Model\Multilang\Dictionary::getFull();

	foreach ($dictionary as $sectionIdx => $section) {
		if (!\Model\Multilang\Dictionary::isUserAuthorized($sectionIdx))
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
						<b><?= $this->word('multilang.label') ?></b>
					</td>
					<?php
					foreach (\Model\Multilang\Ml::getLangs() as $l) {
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
						<input type="text" form="new-word-<?= $sectionIdx ?>" placeholder="<?= $this->word('multilang.new') ?>" data-word/>
					</td>
					<?php
					foreach (\Model\Multilang\Ml::getLangs() as $l) {
						?>
						<td>
							<input type="text" form="new-word-<?= $sectionIdx ?>" data-lang="<?= $l ?>" value=""/>
						</td>
						<?php
					}
					?>
					<td>
						<input type="submit" form="new-word-<?= $sectionIdx ?>" value="<?= $this->word('multilang.insert') ?>"/>
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
						foreach (\Model\Multilang\Ml::getLangs() as $l) {
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
