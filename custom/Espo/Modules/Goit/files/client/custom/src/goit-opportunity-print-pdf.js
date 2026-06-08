define('custom:goit-opportunity-print-pdf', [], function () {

    return class {
        constructor(view) {
            this.view = view;
        }

        printByStagePdf(data) {
            const ids = (data.params && data.params.ids) || [];

            if (!ids.length) {
                Espo.Ui.warning(this.view.translate('selectAtLeastOneRecord', 'messages'));

                return;
            }

            Espo.Ui.notifyWait();

            Espo.Ajax.postRequest('MassAction', {
                entityType: data.entityType,
                action: data.action,
                params: data.params
            }, {
                timeout: 0
            }).then(result => {
                Espo.Ui.notify(false);

                const attachmentId = result && result.ids && result.ids[0] ? result.ids[0] : null;

                if (!attachmentId) {
                    Espo.Ui.error('No se pudo generar el PDF.');

                    return;
                }

                window.open('?entryPoint=download&id=' + attachmentId, '_blank');
            }).catch(error => {
                Espo.Ui.notify(false);

                const message = error && error.message ? error.message : 'No se pudo generar el PDF.';

                Espo.Ui.error(message);
            });
        }
    };
});
