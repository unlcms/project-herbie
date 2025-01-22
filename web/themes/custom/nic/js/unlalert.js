var unlalert = (function($) {
    var dataUrl = 'https://alert.unl.edu/json/unlcap.js';
    var activeIds = [], calltimeout,

    ckPrfx = 'unlAlerts',
    idPrfx = 'unlalert',
    cntSuf = '_content',
    togSuf = '_toggle',

    timeoutPeriod = 30, // how ofter to check for expired data
    dataLifetime = 30, // seconds until the data cookie expires
    ackLifetime = 3600, // seconds until an acknowledgment expires

    WDNgetCookie = function (name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1,c.length);
            }
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length,c.length);
            }
        }
        return null;
    },

    WDNsetCookie = function (name, value, seconds, path, domain) {
        var expires = "";
        if (seconds) {
            var date = new Date();
            date.setTime(date.getTime()+(seconds*1000));
            expires = ";expires="+date.toUTCString();
        }
        if (!path) {
            path = '/';
        } else if (path.charAt(0) !== '/') {
            path = WDN.toAbs(path, window.location.pathname);
        }
        if (!domain) {
            domain = '.unl.edu';
        }
        document.cookie = name+"="+value+expires+";path="+path+";domain="+domain;
    },

    _getClosedAlerts = function() {
        var c = WDNgetCookie(ckPrfx + 'C');
        if (c) {
            return c.split(',');
        }
        return [];
    },

    _pushClosedAlert = function(id) {
        var closed = _getClosedAlerts();
        if ($.inArray(id, closed) != -1) {
            return;
        }
        closed.push(id);
        WDNsetCookie(ckPrfx + 'C', closed.join(','), ackLifetime);
    },

    _checkCookie = function(name) {
        var c = WDNgetCookie(name);
        if (c) {
            return true;
        }
        return false;
    },

    _dataHasExpired = function() {
        return !_checkCookie(ckPrfx + 'Data');
    },

    _hasPreviousAlert = function() {
        return _checkCookie(ckPrfx + 'A');
    },

    _flagPreviousAlert = function(flag) {
        var value = 1, time = 60;
        if (flag === false) {
            value = '';
            time = -1;
        }
        WDNsetCookie(ckPrfx + 'A', value, time);
    },

    _callServer = function() {
        console.log('Checking the alert server for data '+ dataUrl);
        var loadedId = 'lastLoadedCmds'
        $old = $('#' + loadedId);

        if ($old.length) {
            $old.remove();
        }

        $('<script>', {
            "async": "async",
            "defer": "defer",
            "type": "text/javascript",
            "id": loadedId,
            "src": dataUrl
        }).appendTo($('head'));
    },

    _checkIfCallNeeded = function() {
        if (_dataHasExpired() || _hasPreviousAlert()) {
            _callServer();
        }

        clearTimeout(calltimeout);
        calltimeout = setTimeout(_checkIfCallNeeded, timeoutPeriod * 1000);
    },

    dataReceived = function() {
        console.log('UNL Alert data received');
        clearTimeout(calltimeout);
        // Set cookie to indicate time the data was aquired
        WDNsetCookie(ckPrfx + 'Data', 1, dataLifetime);
        calltimeout = setTimeout(_checkIfCallNeeded, (dataLifetime + 1) * 1000);
    },

    alertWasAcknowledged = function(id) {
        var closed = _getClosedAlerts();
        return (closed.indexOf(id) != -1 ? true : false);
    },

    _acknowledgeAlert = function(id) {
        _pushClosedAlert(id);
    },

    toggleAlert = function() {
        console.log('Toggle UNL Alert Visibility');
        var $alert = $('#' + idPrfx),
            $alertToggle = $('#' + idPrfx + togSuf),
            i;

        if ($alert.hasClass('show')) {
            $alert.removeClass('show').closest('body').removeClass(idPrfx + '-shown');
            $alertToggle.find('i').attr('class', 'wdn-icon-attention');
            for (i = 0; i < activeIds.length; i++) {
                _acknowledgeAlert(activeIds[i]);
            }
        } else {
            $alert.addClass('show').closest('body').addClass(idPrfx + '-shown');
            $alertToggle.find('i').attr('class', 'wdn-icon-cancel');
        }
    },

    alertUser = function(root) {
        console.log('Alerting the user');

        _flagPreviousAlert();
        activeIds = [];
        var $alertWrapper = $('#' + idPrfx),
            $alertContent,
            containsExtreme = false,
            allAck = true,
            i,
            info = root.info,
            effectiveDate = '',
            uniqueID,
            web,
            alertContentHTML;

        if (!(info instanceof Array)) {
            info = [info];
        }

        for (i = 0; i < info.length; i++) {
            if (info[i].severity !== 'Extreme') {
                continue;
            }
            containsExtreme = true;
        }

        if (!containsExtreme) {
            return;
        }

        uniqueID = root.identifier || +(new Date);
        activeIds.push(uniqueID);
        allAck = alertWasAcknowledged(uniqueID);

        effectiveDate = new Date(root.sent).toLocaleString();

        var cssId = 'unlalertCss';
        if (!document.getElementById(cssId)) {
            var head  = document.getElementsByTagName('head')[0];
            var link  = document.createElement('link');
            link.id   = cssId;
            link.rel  = 'stylesheet';
            link.type = 'text/css';
            link.href = 'https://unlcms.unl.edu/wdn/templates_4.1/scripts/js-css/unlalert.css';
            link.media = 'all';
            head.appendChild(link);
        }
        for (i = 0; i < info.length; i++) {
            // Add a div to store the html content
            if (!$alertWrapper.length) {
                $alertWrapper = $('<div>', {
                    'id': idPrfx,
                    'class': 'wdn-band wdn-content-slide'
                }).css({
                    'z-index': '1',
                }).insertBefore('#block-system-main');

                $alertContent = $('<div>', {'id': idPrfx + cntSuf});

                $('<div>', {'class': 'page-padding'})
                    .append($alertContent)
                    .appendTo($alertWrapper);
            } else if (i === 0) {
                $alertContent = $('#' + idPrfx + cntSuf).empty();
            }

            web = info[i].web || 'https://www.unl.edu/';

            alertContentHTML = '<h1><span>Emergency UNL Alert:</span> ' + info[i].headline + '</h1>';
            alertContentHTML += '<h2>Issued at ' + effectiveDate + '</h2>';
            alertContentHTML += '<p>' + info[i].description + '<br/>';
            if (info[i].instruction) {
                alertContentHTML += info[i].instruction + '<br/>';
            }
            alertContentHTML += 'Additional info (if available) at <a href="' + web + '">' + web + '</a></p>';

            $alertContent.append(alertContentHTML);
        }

        // Add an visibility toggle tab
        var $alertToggle = $('#' + idPrfx + togSuf);
        if (!$alertToggle.length) {
            $alertToggle = $('<a>', {
                'id': idPrfx + togSuf,
                'href': 'javascript:void(0)'
            })
                .append($('<i>', {'class': 'wdn-icon-attention'}))
                .append($('<span>').text('Toggle Alert Visibility'))
                .click(toggleAlert)
                .prependTo($alertContent.parent());
        }

        if (allAck) {
            console.log('No unlalert display: all were previously acknowledged');
        } else {
            // Only trigger when $alertContent is hidden, otherwise an active, unacknowledged alert will be hidden
            if (!$alertWrapper.hasClass('show')) {
                $alertToggle.click();
            }
        }
    },

    noAlert = function() {
        _flagPreviousAlert(false);
    };

    // push namespace to window to support alert service
    window.unlAlerts = {
        data: {},
        server: {
            init: function() {
                dataReceived();

                // There is an alert if unlAlerts.data.alert.info exists
                if (unlAlerts.data.alert && unlAlerts.data.alert.info) {
                    console.log("Found an alert");
                    $(function() {
                        alertUser(unlAlerts.data.alert);
                    });
                } else {
                    noAlert();
                }
            }
        }
    };

    return {

        initialize: function() {
            _checkIfCallNeeded();
        },

        // Toggle visible alert message open/closed
        toggleAlert: function() {
            toggleAlert();
        }
    };
})(jQuery);

unlalert.initialize();
