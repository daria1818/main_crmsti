BX.ready(function () {


    console.log(BX.AdminFilter);

    class ElementSearch {
        constructor(props) {
            this.props = Object.assign({}, this.getDefaultProps(), props);
            this._dialogSearch = null;
        }

        getDefaultProps() {
            return {
                event: 'onSelectElement',
                lang: 'ru',
                allow_select_parent: 'Y',
                url: '/local/admin/cat_product_search_dialog.php'
            }
        }

        compileUrl() {
            return BX.util.add_url_param(this.props.url, this.props);
        }

        dialogSearch() {

            this._dialogSearch = new BX.CDialog({
                title: 'Поиск элементов',
                width: 1350,
                height: 800,
                content_url: this.compileUrl(),
                ESD: true
            });

            this._dialogSearch.SetButtons([{
                title: BX.message('JS_CORE_WINDOW_SAVE'),
                id: 'savebtn',
                name: 'savebtn',
                className: BX.browser.IsIE() && BX.browser.IsDoctype() && !BX.browser.IsIE10() ? '' : 'adm-btn-save',
                action: () => {
                    this._dialogSearch.Close();
                }
            }]);

            return this;
        }

        getDialog() {
            return this._dialogSearch
        }

        getEvent() {
            return this.props.event;
        }
    }

    const dialog = new ElementSearch();

    $('body').on('focus', 'input[name=PRODUCT]', function () {
        dialog.dialogSearch().getDialog().Show();
    })

    BX.addCustomEvent(dialog.getEvent(), (dataEvent) => {
        $('input[name=PRODUCT]').val($('input[name=PRODUCT]').val() + dataEvent.id + ';');
        dialog.getDialog().Close(); // закроем окно
        $('#ReportsSaleGrid_search').trigger('click');
    });

})

