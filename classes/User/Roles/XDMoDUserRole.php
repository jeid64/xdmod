<?php

namespace User\Roles;

use XDUser;
use User\AuthenticatedRole;
use User\Elements\Module;
use User\Elements\QueryDescripter;

/*
 * 'Xdmod' Role:  (extends Public role and adds the following data (requires an active XD account))
 *
 *      - Personal utilization information for the authenticated user
 *      - Drill down to user's individual job details and allocation information
 *
 */

class XdmodUserRole extends AuthenticatedRole
{
    public function __construct()
    {
        parent::__construct(ROLE_ID_XDMOD);

    } //__construct

    // -----------------------------------

    public function configure(XDUser $user, $simulatedActiveRole = NULL)
    {
        parent::configure($user, $simulatedActiveRole);

      // $p = new \DataWarehouse\Query\Model\Parameter('person_id', '=', $user->getPersonID());

        $this->addParameter('person',  $user->getPersonID());

    } //configure

} // XdmodUserRole

?>
