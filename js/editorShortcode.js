
(function() {
    tinymce.PluginManager.add('natcustom_mce_button', function(editor, url) {
        editor.addButton('natcustom_mce_button', {
            image: url+'/nat.png',
            title: 'Widget Nativery',
            onclick: function() {
                console.log(url);
                editor.windowManager.open({
                    title: 'Inserisci Widget Nativery',
                    body: [{
                        type: 'textbox',
                        name: 'cod',
                        label: 'Codice Widget Nativery',
                        value: ''
                    } ],
                    onsubmit: function(e) {
                        editor.insertContent(
                            '[natWidget cod="' + e.data.cod + '"]'
                        );
                    }
                });
            }
        });
    });
})();