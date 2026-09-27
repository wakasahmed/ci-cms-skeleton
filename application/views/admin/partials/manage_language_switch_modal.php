<div class="modal fade" id="manageLanguageModal" tabindex="-1" aria-labelledby="manageLanguageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h2 class="modal-title fs-5" id="manageLanguageModalLabel">Unsaved changes</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button></div>
            <div class="modal-body">Save your changes before switching languages?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-language-cancel data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-danger" data-language-discard>Discard and Switch</button>
                <?php if (!isset($can_update) || !empty($can_update)) { ?><button type="button" class="btn btn-primary" data-language-save>Save and Switch</button><?php } ?>
            </div>
        </div>
    </div>
</div>

