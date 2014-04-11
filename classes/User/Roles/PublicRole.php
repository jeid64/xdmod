<?php

namespace User\Roles;

/*
 * 'Public' Role (no account is required):
 *
 *      - Overall XD Utilization broken down by resource provider, field of science, time, etc
 *      - Performance data across RP’s – performance of the TAS application kernel suite
 *      - User Like Me (tool to assist users in identifying the resources for their needs)
 *      - No user specific data.
 *      - No resource specific utilization data.
 *      - Limited resource specific application kernel data.
 *
 */

class PublicRole extends \User\aRole
{
    public function __construct()
    {
        parent::__construct(ROLE_ID_PUBLIC);

    }//__construct

}//PublicRole

?>
