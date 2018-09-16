<?php
$langs = (isset($config['langs']) and is_array($config['langs'])) ? $config['langs'] : [];
$defaultLang = (isset($config['default']) and is_string($config['default'])) ? $config['default'] : 'en';
?>
<h1>Configurazione Multilang</h1>

<form action="?" method="post">
	<p>
		<label for="langs">Lingue supportate (separati da virgola)</label><br/>
		<input type="text" name="langs" value="<?= entities(implode(',', $langs)) ?>" id="langs" style="width: 90%"/>
	</p>
	<p>
		<label>Lingua di default</label><br/>
		<select name="default">
			<?php
			foreach ($langs as $l) {
				?>
				<option value="<?= entities($l) ?>"<?= $l === $defaultLang ? ' selected' : '' ?>><?= entities($l) ?></option><?php
			}
			?>
		</select>
	</p>
	<p>
		<input type="submit" value="Salva"/>
	</p>
</form>
