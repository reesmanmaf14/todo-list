/*
 * Reusable AJAX helper built on the native Fetch API (no jQuery needed).
 *
 * Usage:
 *   sendAjax(url, type, data, successCallback, errorCallback);
 *
 *   - url:             where to send the request
 *   - type:            HTTP method: 'GET', 'POST', 'PUT', 'PATCH' or 'DELETE'
 *   - data:            plain object, sent as JSON (or null for no body)
 *   - successCallback: function (response)            → called for 2xx responses
 *   - errorCallback:   function (xhr, status, error)  → called for everything else
 *
 * Like jQuery's $.ajax, the error callback receives an `xhr`-like object:
 *   xhr.status        HTTP status code (0 when the server could not be reached)
 *   xhr.responseJSON  parsed JSON body, or undefined if the body was not JSON
 *   xhr.responseText  raw response body
 *
 * sendAjax() returns a Promise that resolves after the callback has run,
 * so callers can wait for it (e.g. to re-enable a submit button).
 */

// CSRF token from <meta name="csrf-token"> in layouts/app.blade.php
const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

async function sendAjax(url, type, data, successCallback, errorCallback) {
    const options = {
        method: type,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',          // makes Laravel answer with JSON
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF,
        },
    };

    if (data !== null && data !== undefined && type !== 'GET') {
        options.body = JSON.stringify(data);
    }

    let response;

    try {
        response = await fetch(url, options);
    } catch (networkError) {
        if (typeof errorCallback === 'function') {
            errorCallback({ status: 0, responseJSON: undefined, responseText: '' }, 'error', 'Network error');
        }
        return;
    }

    // The body may be empty or not JSON (e.g. an HTML error page), so don't let parsing crash
    const responseText = await response.text().catch(function () {
        return '';
    });

    let responseJSON;
    let parseFailed = false;

    if (responseText) {
        try {
            responseJSON = JSON.parse(responseText);
        } catch (error) {
            parseFailed = true;
        }
    }

    const xhr = { status: response.status, responseJSON: responseJSON, responseText: responseText };

    if (response.ok && !parseFailed) {
        if (typeof successCallback === 'function') {
            successCallback(responseJSON ?? null);
        }
    } else if (typeof errorCallback === 'function') {
        errorCallback(xhr, parseFailed && response.ok ? 'parsererror' : 'error', response.statusText);
    }
}

/*
 * Turns a failed request (the `xhr` passed to an error callback) into a message for the user.
 * Returns { text, withRefreshButton }.
 * `overrides.forbidden` lets each operation use its own 403 wording.
 */
function ajaxErrorMessage(xhr, overrides = {}) {
    if (xhr.status === 0) {
        return { text: 'Could not reach the server. Check your internet connection and try again.', withRefreshButton: false };
    }

    if (xhr.status === 401 || xhr.status === 419) {
        return { text: 'Your session has expired. Please refresh the page (and log in again if asked), then try again.', withRefreshButton: true };
    }

    if (xhr.status === 403) {
        return { text: overrides.forbidden || 'You are not allowed to do that.', withRefreshButton: false };
    }

    if (xhr.status === 404) {
        return { text: 'This task no longer exists. Please refresh the page.', withRefreshButton: true };
    }

    return { text: 'Something went wrong on the server. Please try again later.', withRefreshButton: false };
}
