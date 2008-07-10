<?php


class LoginController
{
    public function loginCallback($args, $action, $mem_for_redirect)
    {
        $count = $action->count;
        $redirect_req = $action->redirect_req;
        $model = new LoginModel();
        
        $post = $args->post;
        $request = $args->request;

        
        // note:
        // all the echos are buffered by the framework,
        // and sent out after the redirect.
        
        
        if (empty($post['u'])) {
            echo 'no username given.';
            
        } else if (!$bw_member = $model->getBWMemberByUsername($username = trim($post['u']))) {
            echo 'member "'.$username.'" does not exist';
            
        } else if (!is_string($post['p'])) {
            echo 'no password given';
            
        } else if (!$model->checkBWPassword($bw_member, $password = trim($post['p']))) {
            echo 'wrong password given for username '.$bw_member->Username;
            
        } else {
            // bw member exists, and pw matches.
            
            // what about the tb user?
            if (!$tb_user = $model->getTBUserForBWMember($bw_member)) {
                // no, he's not in TB. Buuuh.
                // Create new?
                echo "<div>no tb user found with handle = '$bw_member->Username'. Trying to repair that.</div>";
                if (!$success = $model->createMissingTBUser($bw_member, $password)) {
                    echo "<div>Didn't work.</div>";
                } else if ('same_id' == $success) {
                    echo "
                    <div>Created new tb user with same id</div>
                    <div>(Username: '$bw_member->Username', BW-id: $bw_member->id, TB-id: $tb_user->id)</div>";
                } else if ('different_id' == $success) {
                    echo "
                    <div>Created new tb user with different id</div>
                    <div>(Username: '$bw_member->Username', BW-id: $bw_member->id, TB-id: $tb_user->id)</div>";
                }
            }
            if (!$tb_user = $model->getTBUserForBWMember($bw_member)) {
                echo "<div>still no tb user found with handle = '$bw_member->Username'. Giving up.</div>";
            } else {
                if (!$model->checkTBPassword($tb_user, $password)) {
                    // tb password didn't match. well, who cares.
                    // TODO: log this.
                } else {
                    // tb pw does match! yeah.
                    // can now change the tables and session so that user is logged in.
                }
                if (!$model->setBWMemberAsLoggedIn($bw_member)) {
                    // something in the status was not ok.
                    echo "<div>Your status is '$bw_member->Status'. No chance to log in.. we are sorry!</div>";
                } else {
                    echo "<div>login successful</div>";
                    $model->setupBWSession($bw_member);
                    $model->setTBUserAsLoggedIn($tb_user);
                    if (isset($request[0]) && 'login' == $request[0]) {
                        $redirect_url = implode('/', array_slice($request, 1));
                        if (!empty($_SERVER['QUERY_STRING'])) {
                            $redirect_url .= '?'.$_SERVER['QUERY_STRING'];
                        }
                        return $redirect_url;
                    }
                }
            }
        }
    }
}


?>