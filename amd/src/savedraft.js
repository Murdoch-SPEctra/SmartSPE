define(['jquery'], function($) {
    return {
        init: function (speid, sesskey) {
            const COOLDOWN = 5 * 1000; 
            let timeout;
            const form = $('.mform'); 
            const lastSavedSpan = $('#draft-last-saved');
            console.log("Auto-save draft initialized for SPE ID:", speid);
            form.on('input change', function () {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    console.log("Auto-saving draft...");
                    const data = form.serialize();

                    $.post(M.cfg.wwwroot + '/mod/smartspe/student/savedraft.php', {
                        speid: speid,
                        sesskey: sesskey,
                        data: data
                    })
                    .done(function (response) {
                        console.log('Draft saved');
                        const json = JSON.parse(response);
                        const now = new Date().toLocaleTimeString();
                        if (json.status === 'ok') {
                            console.log(json)
                            lastSavedSpan.text('Last saved at ' + now);
                            lastSavedSpan.css('color', 'green');
                        } else {
                            console.error('Save error:', json.message);
                            lastSavedSpan.text('Save failed at ' + now);
                            lastSavedSpan.css('color', 'red');
                        }
                        
                    })
                    .fail(function(xhr) {
                        console.error('Save failed:', xhr.responseText);
                    });
                }, COOLDOWN); // Save every COOLDOWN seconds after idle
            });
        }
    };
});
