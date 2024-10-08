/**
 * @file
 * Provides a Nightwatch command for fetching a URL with cookies in Drupal.
 */

module.exports = class ThDrupalFetchURL {
  /**
   * Custom Nightwatch command to fetch a URL with cookies in Drupal.
   *
   * @param {string} pathname
   *   The path to fetch.
   * @param {string} [method='GET']
   *   The HTTP method to use.
   * @param {string} [body='']
   *   The body of the request.
   * @param {Function} [callback=undefined]
   *   The callback function to execute after the fetch.
   *
   * @return {Object}
   *   The result object containing the response.
   */
  async command(pathname, method = 'GET', body = '', callback = undefined) {
    // We need to use all arguments array here.
    // eslint-disable-next-line prefer-rest-params
    if (arguments.length === 2 && typeof arguments[1] === 'function') {
      // We need to use all arguments array here.
      // eslint-disable-next-line prefer-rest-params
      [pathname, callback] = arguments;
      method = 'GET';
    }

    const url = `${process.env.DRUPAL_TEST_BASE_URL}${pathname}`;
    let cookies;
    let response;

    await this.api
      .perform(async () => {
        cookies = await this.api.getCookies();
      })
      // Executing the fetch in a separate perform block, because executing
      // in the same block leads to instant returning the cookies array instead
      // of the actual response. Looks like a strange behavior of the
      // Nightwatch.
      .perform(async () => {
        response = await ThDrupalFetchURL.fetchWithCookies(
          url,
          method,
          body,
          cookies,
        );
      })
      .perform(async () => {
        const promises = [];
        // We need to use the `for` loop here, because the `await` inside the
        // forEach loop doesn't work.
        // eslint-disable-next-line no-restricted-syntax
        for (const cookie of response.cookies) {
          if (cookie.maxAge === 0) {
            promises.push(this.api.deleteCookie(cookie.name));
          } else {
            promises.push(this.api.setCookie(cookie));
          }
        }
        await Promise.all(promises);
      });

    const result = {
      status: 0,
      value: response,
    };

    if (typeof callback === 'function') {
      const self = this;
      callback.call(self, result);
    }
    return result;
  }

  static parseSetCookie(cookieString) {
    // Split the cookie string by ';' to get the individual pieces
    const parts = cookieString.split(';').map((part) => part.trim());

    // The first part is always the key=value pair
    const [nameValue, ...attributes] = parts;

    const [name, ...valueParts] = nameValue.split('=');
    const value = valueParts.join('=');

    const cookieObject = {
      name,
      value,
    };

    // We need to use the for loop here, because the `await` inside the
    // forEach loop doesn't work.
    // eslint-disable-next-line no-restricted-syntax
    for (const attribute of attributes) {
      const [attrName, ...attrValueParts] = attribute.split('=');
      const attrValue = attrValueParts.join('=');

      // Normalize attribute names to camelCase for consistency
      const normalizedAttrName = attrName
        .replace(/-([a-z])/g, (match, p1) => p1.toUpperCase())
        .replace(/-/g, '')
        .replace(/^(.)/, (match, p1) => p1.toLowerCase());

      // Nightwatch expects the `expiry` to be a timestamp in seconds instead of
      // the `expires` name.
      if (attrName === 'expires') {
        cookieObject.expiry = Math.round(new Date(attrValue).getTime() / 1000);
        // If the attribute is a flag (like HttpOnly, Secure, etc.), just set it to true
      } else if (attrValue === '') {
        cookieObject[normalizedAttrName] = true;
      } else {
        cookieObject[normalizedAttrName] = attrValue.trim() || null;
      }
    }

    return cookieObject;
  }

  /**
   * Fetches a URL with cookies.
   *
   * @param {string} url
   *   The URL to fetch.
   * @param {string} [method='GET']
   *   The HTTP method to use.
   * @param {string} [body='']
   *   The body of the request.
   * @param {Array} [cookiesObjects=[]]
   *   An array of cookie objects.
   *
   * @return {Promise<Object>}
   *   A promise that resolves to the response data.
   */
  static async fetchWithCookies(
    url,
    method = 'GET',
    body = '',
    cookiesObjects = [],
  ) {
    // Convert the array of cookie objects to a single string for the Cookie header
    const cookieHeader = cookiesObjects
      .map((cookie) => `${cookie.name}=${cookie.value}`)
      .join('; ');
    const options = {
      method,
      headers: {
        Cookie: cookieHeader,
      },
    };
    if (method !== 'GET' && method !== 'HEAD') {
      options.body = body;
    }
    const response = await fetch(url, options);
    const data = await response.text();
    const headers = {};
    response.headers.forEach((value, name) => {
      // Actually, Node.js function fetch() support multiple values only for
      // `set-cookie`,for other multiple headers overwrites the previous
      // value.
      if (name === 'set-cookie') {
        if (!headers[name]) {
          headers[name] = [];
        }
        headers[name].push(value);
      } else {
        headers[name] = value;
      }
    });

    const cookies = [];
    if (headers['set-cookie']) {
      // We need to use the for loop here, because the `await` inside the
      // forEach loop doesn't work.
      // eslint-disable-next-line no-restricted-syntax
      for (const cookieString of headers['set-cookie']) {
        cookies.push(ThDrupalFetchURL.parseSetCookie(cookieString));
      }
    }

    const responseData = {
      data,
      status: response.status,
      statusText: response.statusText,
      headers,
      cookies,
      config: {
        url,
        drupalPath: url.replace(process.env.DRUPAL_TEST_BASE_URL, ''),
        method,
        headers: options.headers,
      },
    };
    return responseData;
  }
};
