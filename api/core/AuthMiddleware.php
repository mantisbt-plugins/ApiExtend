<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A webservice interface to Mantis Bug Tracker
 *
 * @package MantisBT
 * @copyright Copyright MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */

require_api( 'authentication_api.php' );
require_api( 'user_api.php' );

/**
 * A middleware class that handles authentication and authorization to access APIs.
 */
class AuthMiddleware {
	public function __invoke( \Slim\Http\Request $request, \Slim\Http\Response $response, callable $next ) {
		$t_authorization_header = $request->getHeaderLine( HEADER_AUTHORIZATION );
		if( empty( $t_authorization_header ) ) {
			# Since authorization header is empty, check if user is authenticated by checking the cookie
			# This mode is used when Web UI javascript calls into the API.
			if( auth_is_user_authenticated() ) {
				$t_username = user_get_username( auth_get_current_user_id() );
				$t_password = auth_get_current_user_cookie( /* auto-login-anonymous */ false );
				$t_login_method = LOGIN_METHOD_COOKIE;
			} 
			else 
			{
				$t_username = auth_anonymous_account();
				if ( !is_blank( plugin_config_get( 'api_user', '' ) ) && !is_blank(plugin_config_get( 'api_token', '' ) ) ) 
				{
					$t_username = plugin_config_get( 'api_user' );
					$t_password = plugin_config_get( 'api_token' );
					$t_login_method = LOGIN_METHOD_API_TOKEN;
				}
				else if( !auth_anonymous_enabled() || empty( $t_username ) ) 
				{
					return $response->withStatus( HTTP_STATUS_UNAUTHORIZED, 'API token required' );
				}
				else
				{
					$t_login_method = LOGIN_METHOD_ANONYMOUS;
					$t_password = '';
				}
			}
		} else {
			# TODO: add an index on the token hash for the method below
			$t_user_id = api_token_get_user( $t_authorization_header );
			if( $t_user_id === false ) {
				return $response->withStatus( HTTP_STATUS_FORBIDDEN, 'API token not found' );
			}

			# use api token
			$t_login_method = LOGIN_METHOD_API_TOKEN;
			$t_password = $t_authorization_header;
			$t_username = user_get_username( $t_user_id );
		}

		if( mci_check_login( $t_username, $t_password ) === false ) {
			return $response->withStatus( HTTP_STATUS_FORBIDDEN, 'Access denied' );
		}

		# Now that user is logged in, check if they have the right access level to access the REST API.
		# Don't treat web UI calls with cookies as API calls that need to be disabled for certain access levels.
		if( $t_login_method != LOGIN_METHOD_COOKIE && !mci_has_readonly_access() ) {
			return $response->withStatus( HTTP_STATUS_FORBIDDEN, 'Higher access level required for API access' );
		}

		$t_force_enable = $t_login_method == LOGIN_METHOD_COOKIE;
		return $next( $request->withAttribute( ATTRIBUTE_FORCE_API_ENABLED, $t_force_enable ), $response )->
			withHeader( HEADER_USERNAME, $t_username )->
			withHeader( HEADER_LOGIN_METHOD, $t_login_method );
	}
}
