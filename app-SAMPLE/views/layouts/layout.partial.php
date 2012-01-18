<div id="cowl-meta-data">
	<input type="hidden" id="cowl-meta-data-body-id" value="body-<?php p($request->pieces[0]); ?>" />
	<input type="hidden" id="cowl-meta-data-js-command-path" value="<?php p(Current::$request->getInfo('js_page_path')); ?>" />
	<input type="hidden" id="cowl-meta-data-js-command" value="<?php p(strtolower(implode('.', $request->pieces))); ?>" />
	<input type="hidden" id="cowl-meta-data-js-method" value="<?php p($request->method); ?>" />
</div>
<?php include($this->template); ?>