var parts = location.hostname.split(".");
if (parts.length > 2 && "com" !== parts[1] && "co" !== parts[1]) var subdomain = parts.shift(),
    domain = parts.join(".");
else var domain = location.hostname;
! function() {
    function scriptLoadHandler() {
        $cf = jQuery.noConflict(!0), void 0 !== window.prevjQuery && (jQuery = window.prevjQuery), campaigns()
    }

    function campaigns() {
        function getParameterByName(name) {
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(location.search);
            return null === results ? "" : decodeURIComponent(results[1].replace(/\+/g, " "))
        }

        function decodeURIComponentSafe(uri, mod) {
            var arr, l, x, out = new String,
                i = 0;
            for ("undefined" == typeof mod ? mod = 0 : 0, arr = uri.split(/(%(?:d0|d1)%.{2})/), l = arr.length; l > i; i++) {
                try {
                    x = decodeURIComponent(arr[i])
                } catch (e) {
                    x = mod ? arr[i].replace(/%(?!\d+)/g, "%25") : arr[i]
                }
                out += x
            }
            return out
        }

        function getQueryString() {
            var search = location.search.substring(1);
            if ("" !== search) var data = search ? JSON.parse('{"' + search.replace(/&/g, '","').replace(/=/g, '":"') + '"}', function(key, value) {
                return "" === key ? value : decodeURIComponentSafe(value)
            }) : {};
            else var data = {};
            return data
        }

        function setReady() {
            for (var callback; callback = queue.shift();) callback();
            isReady = !0
        }

        function ready(callback) {
            isReady ? callback() : queue.push(callback)
        }

        function generateId() {
            return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function(c) {
                var r = 16 * Math.random() | 0,
                    v = "x" == c ? r : 3 & r | 8;
                return v.toString(16)
            })
        }

        function saveEventQueue() {
            canStringify && setCookie("cf_events", JSON.stringify(eventQueue), 1)
        }

        function trackEvent(event) {
            ready(function() {
                canStringify && $cf.ajax({
                    type: "POST",
                    url: eventsUrl,
                    data: {
                        event: event
                    },
                    dataType: "json",
                    success: function() {
                        for (var i = 0; i < eventQueue.length; i++)
                            if (eventQueue[i].id == event.id) {
                                eventQueue.splice(i, 1);
                                break
                            }
                        saveEventQueue()
                    }
                })
            })
        }

        function snippetEmbed(snippet) {
            var cta_id = $cf(snippet).attr("cta-id"),
                website_id = $cf(snippet).attr("website-id");
            "1367" == website_id && $cf.cachedScript("https://assets.convertflow.com/scripts/websites/1367/cta/" + cta_id + ".js").done(function() {
                $cf(snippet).find(".cf-step").first().show()
            })
        }

        function urlIdentification() {
            var email = void 0;
            return $cf.each(["cf_email", "email", "Email", "EmailAddress", "email_address", "emailaddresss"], function(index, key) {
                var parameter = getParameterByName(key);
                return "" !== parameter ? (email = parameter, !1) : void 0
            }), email
        }

        function trackingUIDs() {
            $cf(window).load(function() {
                setTimeout(function() {
                    var tracking_ids = {};
                    0 !== Object.keys(tracking_ids).length && $cf.ajax({
                        type: "POST",
                        url: "https://api.convertflow.com/websites/1367/visitors/uids",
                        data: {
                            visitor_token: visitorId,
                            uids: tracking_ids
                        },
                        dataType: "json"
                    })
                }, 1e3)
            })
        }

        function trackSubmits() {
            $cf(document).on("submit", "form", function(e) {
                var form = e.target;
                if (0 == $cf(form).hasClass("new_contact")) {
                    var fields = {
                            firstname: "name",
                            first_name: "name",
                            lastname: "last_name",
                            last_name: "last_name",
                            email: "email",
                            phone: "phone",
                            website: "url",
                            city: "city",
                            country: "country",
                            address: "address",
                            state: "state",
                            zip: "zipcode"
                        },
                        submission = {};
                    $cf.each(fields, function(k, v) {
                        void 0 == submission[v] && ($cf(form).find('input[type="' + k + '"]').length > 0 ? submission[v] = $cf(form).find('input[type="' + k + '"]').val() : $cf(form).find('[name*="' + k + '"]').length > 0 ? submission[v] = $cf(form).find('[name*="' + k + '"]').val() : $cf(form).find('[id*="' + k + '"]').length > 0 ? submission[v] = $cf(form).find('[id*="' + k + '"]').val() : $cf(form).find('[placeholder*="' + k + '"]').length > 0 && (submission[v] = $cf(form).find('[placeholder*="' + k + '"]').val()))
                    });
                    var filter = /^[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;
                    if (void 0 !== submission.email && filter.test(submission.email)) {
                        submission.source_url = window.location.href;
                        var event = {
                            event_type: "Submission",
                            visitor_token: visitorId,
                            website_id: 1367,
                            url: window.location.href,
                            data: submission
                        };
                        $cf.ajax({
                            type: "POST",
                            url: eventsUrl,
                            data: {
                                event: event
                            },
                            dataType: "json"
                        })
                    }
                }
            })
        }

        function initiateTestMode() {
            window.cf_test = {
                site: {
                    id: 1367,
                    status: !1,
                    conditions: [],
                    ctas: []
                },
                broadcasts: [],
                flows: [],
                goals: [],
                standalones: []
            }, void 0 !== person.logged_in && "true" == person.logged_in && $cf(document).ready(function() {
                $cf.ajax({
                    type: "GET",
                    url: "https://app.convertflow.com/websites/1367/testmode",
                    dataType: "JSONP",
                    success: function(data) {
                        window.cf_logged_in = !0, $cf("#cfTestStart").remove(), $cf("#cfTestWidget").remove(), $cf("body").append(data.html)
                    }
                })
            })
        }

        function newVisitor(visit, targeting_data) {
            visitorId = generateId(), setCookie("cf_1367_id", visitorId, 10512e3);
            var payload = {
                "new": !0,
                visit: visit,
                visitor_token: visitorId,
                website_id: 1367,
                visitor: {
                    visitor_token: visitorId,
                    platform: convertflow.platform || "Web",
                    landing_page: window.location.href,
                    referral_source: document.referrer,
                    website_id: 1367
                }
            };
            targeting_data = $cf.extend(targeting_data, {
                "new": !0
            });
            var payload = $cf.extend({}, payload, targeting_data);
            $cf.ajax({
                type: "POST",
                url: "https://api.convertflow.com/websites/1367/visitors",
                contentType: "application/json; charset=utf-8",
                data: payload,
                dataType: "JSONP",
                success: function(data) {
                    window.person = data, window.convertflow.person = data, window.dispatchEvent(new CustomEvent("cfReady", {
                        detail: !1
                    }));
                    var email = urlIdentification();
                    void 0 === person.email && void 0 !== email ? convertflow.identify({
                        email: email
                    }) : cfCampaigns($cf, person)
                }
            })
        }

        function cfCampaigns($cf, person) {
            $cf.cachedScript = function(url, options) {
                return options = $cf.extend(options || {}, {
                    dataType: "script",
                    cache: !0,
                    async: !1,
                    url: url
                }), $cf.ajax(options)
            }, $cf.cachedScript("https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js").done(function() {
                void 0 !== window.WebFont && void 0 == window.$cf.fonts && (WebFont.load({
                    google: {
                        families: ["Montserrat:400,400italic,700,700italic", "Roboto:400,400italic,500,500italic,700,700italic", "Lato:400,400italic,700,700italic", "Open Sans:400,400italic,700,700italic", "Oswald:400,700"]
                    }
                }), window.$cf.fonts = !0)
            }), $cf(".cf-cta-snippet").length > 0 ? $cf(".cf-cta-snippet").each(function(index, snippet) {
                snippetEmbed(snippet)
            }) : $cf(document).ready(function() {
                $cf(".cf-cta-snippet").each(function(index, snippet) {
                    snippetEmbed(snippet)
                })
            }), page = $cf(location).attr("href").split(/[?#]/)[0].replace(/\/$/, "").replace(/^https?\:\/\//, "").replace(/^www./, ""), initiateTestMode();
            var conditions = [];
            window.cf_test.site.conditions = conditions, "function" == typeof cfTest1367 && cfTest1367(), (0 == conditions.length || conditions.length > 0 && (0 == $cf.grep(conditions, function(s) {
                return void 0 !== s.and
            }).length || 0 == $cf.grep(conditions, function(s) {
                return 0 == s.and
            }).length) && (0 == $cf.grep(conditions, function(s) {
                return void 0 !== s.or
            }).length || $cf.grep(conditions, function(s) {
                return 1 == s.or
            }).length > 0)) && (trackSubmits(), window.cf_test.site.status = !0, "function" == typeof cfTest1367 && cfTest1367(), void 0 !== person.data.flows && (flow1317 = $cf.grep(person.data.flows, function(f) {
                return 1317 == f.id
            })[0]), window.cf_test.flows[1317] = {
                id: 1317,
                name: "Nail Fungus Page",
                stages: {}
            }, void 0 == person.data.flows || void 0 == flow1317 ? ("function" == typeof cfTest1367 && cfTest1367(), window.cf_test.flows[1317].stages[2542] = {
                id: 2542,
                position: 1,
                flow_id: 1317,
                name: ""
            }, "function" == typeof cfTest1367 && cfTest1367()) : 1 == flow1317.position && (window.cf_test.flows[1317].stages[2542] = {
                id: 2542,
                position: 1,
                flow_id: 1317,
                name: ""
            }, "function" == typeof cfTest1367 && cfTest1367()), void 0 !== person.data.flows && (flow1326 = $cf.grep(person.data.flows, function(f) {
                return 1326 == f.id
            })[0]), window.cf_test.flows[1326] = {
                id: 1326,
                name: "Shingles Page",
                stages: {}
            }, void 0 == person.data.flows || void 0 == flow1326 ? ("function" == typeof cfTest1367 && cfTest1367(), window.cf_test.flows[1326].stages[2554] = {
                id: 2554,
                position: 1,
                flow_id: 1326,
                name: ""
            }, "function" == typeof cfTest1367 && cfTest1367()) : 1 == flow1326.position && (window.cf_test.flows[1326].stages[2554] = {
                id: 2554,
                position: 1,
                flow_id: 1326,
                name: ""
            }, "function" == typeof cfTest1367 && cfTest1367()))
        }
        void 0 == window.$cf && (window.$cf = jQuery), convertflow = {
            domain: domain
        };
        var visitorId, page = convertflow.page || window.location.pathname,
            eventsUrl = "https://api.convertflow.com/websites/1367/events",
            eventQueue = [],
            canStringify = "undefined" != typeof JSON && "undefined" != typeof JSON.stringify,
            queue = [],
            isReady = !1;
        ! function() {
            function CustomEvent(event, params) {
                params = params || {
                    bubbles: !1,
                    cancelable: !1,
                    detail: void 0
                };
                var evt = document.createEvent("CustomEvent");
                return evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail), evt
            }
            return "function" == typeof window.CustomEvent ? !1 : (CustomEvent.prototype = window.Event.prototype, void(window.CustomEvent = CustomEvent))
        }(), new CustomEvent("cfReady", {
            detail: {
                message: "ConvertFlow is initialized",
                time: new Date
            },
            bubbles: !0,
            cancelable: !0
        }), window.setCookie = function(name, value, ttl) {
            var expires = "",
                cookieDomain = "";
            if (ttl) {
                var date = new Date;
                date.setTime(date.getTime() + 60 * ttl * 1e3), expires = "; expires=" + date.toGMTString()
            }
            domain && (cookieDomain = "; domain=" + domain), document.cookie = name + "=" + escape(value) + expires + cookieDomain + "; path=/"
        }, window.getCookie = function(name) {
            var re = new RegExp(name + "=([^;]+)"),
                value = re.exec(document.cookie);
            return null != value ? unescape(value[1]) : null
        }, convertflow.track = function(event_type, properties) {
            var event = {
                event_type: event_type,
                visitor_token: visitorId,
                website_id: 1367,
                url: window.location.href,
                data: properties
            };
            if (null !== getCookie("cf_1367_visitor_id")) {
                var visitor_id = {
                    visitor_id: getCookie("cf_1367_visitor_id")
                };
                event = $cf.extend({}, event, visitor_id)
            }
            if (null !== getCookie("cf_1367_contact_id")) {
                var contact_id = {
                    contact_id: getCookie("cf_1367_contact_id")
                };
                event = $cf.extend({}, event, contact_id)
            }
            eventQueue.push(event), saveEventQueue(), setTimeout(function() {
                trackEvent(event)
            }, 1e3)
        }, convertflow.identify = function(data) {
            if (void 0 !== data.email && (void 0 !== window.person && void 0 == person.email || 1 == data.override && data.email !== person.email)) {
                var filter = /^[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;
                if (filter.test(data.email)) {
                    var payload = {
                        contact: {
                            website_id: 1367,
                            email: data.email,
                            visitor_token: visitorId
                        },
                        targeting_data: targeting_data
                    };
                    $cf.ajax({
                        type: "POST",
                        url: "https://api.convertflow.com/websites/1367/contacts",
                        dataType: "JSON",
                        data: payload,
                        success: function(response) {
                            window.person = response, window.convertflow.person = response, $cf(".convertflow-cta").remove(), 1 == window.hookHasTriggered && (window.hookHasTriggered = void 0), cfCampaigns($cf, person)
                        },
                        error: function() {
                            console.log("ConvertFlow couldn't identify contact")
                        }
                    })
                }
            }
        }, convertflow.load = function(cta_id) {
            if (0 == $cf("#cta" + cta_id).length) {
                var cta = document.createElement("div");
                $cf(cta).attr("id", "cta" + cta_id).css("display", "none"), $cf(cta).addClass("convertflow-cta"), $cf(document.body).append(cta), $cf.cachedScript("https://assets.convertflow.com/scripts/websites/1367/cta/" + cta_id + ".js")
            }
        };
        try {
            eventQueue = JSON.parse(getCookie("cf_events") || "[]")
        } catch (e) {}
        for (var i = 0; i < eventQueue.length; i++) trackEvent(eventQueue[i]);
        visitorId = getCookie("ahoy_visitor") || getCookie("cf_1367_id"), targeting_data = {
            url: window.location.href,
            tags: !1,
            visits: !1,
            referral: !1,
            params: !1,
            engagements: !1,
            completions: !1
        }, targeting_data = $cf.extend({}, targeting_data, {
            infusionsoft: !0
        }), null !== getParameterByName("Id") && getParameterByName("Id").length > 0 && (targeting_data = $cf.extend({}, targeting_data, {
            Id: getParameterByName("Id")
        }));
        var visit = {
            event_type: "Visit",
            url: window.location.href,
            website_id: 1367,
            data: {
                title: document.title,
                page: page
            }
        };
        if ("" !== getQueryString() && (visit.data = $cf.extend({}, visit.data, {
                params: getQueryString(),
                referrer: document.referrer
            })), visitorId) {
            var person_data = {
                    visitor_token: visitorId,
                    visit: visit,
                    visitor: {
                        visitor_token: visitorId,
                        platform: convertflow.platform || "Web",
                        landing_page: window.location.href,
                        referral_source: document.referrer,
                        website_id: 1367
                    }
                },
                person_data = $cf.extend({}, person_data, targeting_data);
            setReady(), $cf.ajax({
                type: "GET",
                url: "https://api.convertflow.com/websites/1367/visitors",
                contentType: "application/json; charset=utf-8",
                data: person_data,
                dataType: "JSONP",
                success: function(data) {
                    window.person = data, window.convertflow.person = data, window.dispatchEvent(new CustomEvent("cfReady", {
                        detail: !1
                    }));
                    var email = urlIdentification();
                    void 0 === person.email && void 0 !== urlIdentification() ? convertflow.identify({
                        email: email
                    }) : cfCampaigns($cf, person)
                },
                error: function() {
                    newVisitor(visit, targeting_data)
                }
            })
        } else newVisitor(visit, targeting_data);
        trackingUIDs()
    }
    if (void 0 !== window.jQuery && (window.prevjQuery = window.jQuery), void 0 === window.jQuery || "2.0.3" !== window.jQuery.fn.jquery && "1.9.1" !== window.jQuery.fn.jquery) {
        var script_tag = document.createElement("script");
        script_tag.setAttribute("type", "text/javascript"), script_tag.setAttribute("src", "https://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"), script_tag.readyState ? script_tag.onreadystatechange = function() {
            ("complete" == this.readyState || "loaded" == this.readyState) && scriptLoadHandler()
        } : script_tag.onload = scriptLoadHandler, (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag)
    } else $cf = window.jQuery, console.log("Existing jQuery is " + $cf.fn.jquery), campaigns()
}();