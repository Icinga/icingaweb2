<?php

namespace Icinga\Controllers;

use Icinga\Application\Icinga;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Util\Format;
use Icinga\Util\Json;
use Icinga\Web\Notification;
use ipl\Sql\Expression;
use ipl\Web\Compat\CompatController;
use ipl\Web\Filter\QueryString;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Socket\FixedUriConnector;
use React\Socket\UnixConnector;

use function Clue\React\Block\await;

class TestController extends CompatController
{
    public function separateRefreshAction()
    {
        $this->view->content = 'About to be...';

        $this->getTabs()->add('separate_refresh', [
            'label'     => 'Separate Refresh',
            'url'       => 'test/separate-refresh',
            'active'    => true
        ]);
    }

    public function separateContentAction()
    {
        $this->view->content = 'Refreshed!';

        $this->setAutorefreshInterval(10);
        $this->_helper->layout()->disableLayout();
    }

    public function memErrorAction()
    {
        ini_set('memory_limit', Format::unpackShorthandBytes('10MB'));

        $iterations = 10;

        $s = '';
        while (true) {
            $s .= 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        }


        $images = [];
        for ($i = 0; $i < $iterations; $i++) {
            //$images[] = file_get_contents(Icinga::app()->getBaseDir(). '/public/img/icingaweb2-background.jpg');
        }

        die('works');
    }

    public function nullfilterAction()
    {
        $obj = (object) ['foo' => null];
        //$filter = Filter::expression('foo', '=', '*');
        $filter = Filter::where('foo', '*');
        $filter->setCaseSensitive(false);
        $filter->matches($obj);
        exit;
    }

    public function notificationTestAction()
    {
        Notification::error('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut l');
        Notification::info('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut l');
        Notification::warning('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut l');
        Notification::success('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut l');
    }

    public function testAction()
    {
        $this->view->title = 'Test';
    }

    public function forwardAction()
    {
        $this->view->title = 'foo';
        $this->forward('test');
    }

    public function daemonAction()
    {
        $connector = new FixedUriConnector(
            'unix:///run/icingawebd.socket',
            new UnixConnector()
        );

        $browser = new Browser($connector);

        $response = await($browser->get('http://localhost/info'));
        /** @var ResponseInterface $response */
        echo $response->getBody(); exit;
    }



    use Database;
    protected $requiresAuthentication = false;

    public function objectIdsAction()
    {
        $type = $this->params->shiftRequired('type');
        $objectId = $this->params->shift('object_id');

        $roles = Json::decode(file_get_contents(Icinga::app()->getConfigDir() . '/' . 'roles.json'));

        switch ($type) {
            case 'host':
                $query = Host::on($this->getDb());
                break;
            case 'service':
                $query = Service::on($this->getDb());
                break;
            default:
                $this->httpBadRequest('invalid type');
        }

        $query->columns('id');

        $userQueries = [];
        foreach ($roles as $username => $restrictions) {
            $filter = \ipl\Stdlib\Filter::any();
            foreach ($restrictions as $restriction) {
                $filter->add(QueryString::parse($restriction));
            }

            $subQuery = clone $query;
            $subQuery->getResolver()->setAliasPrefix("${username}_");
            $linkToParentQuery = [sprintf('%s.id = %s.id', $query->getResolver()->getAlias($query->getModel()), $subQuery->getResolver()->getAlias($subQuery->getModel()))];

            list($stmt, $values) = $this->getDb()->getQueryBuilder()->assembleSelect(
                $subQuery
                    ->columns([new Expression('1')])
                    ->filter($filter)
                    ->limit(1)
                    ->assembleSelect()
                    ->where($linkToParentQuery)
                    ->resetOrderBy()
            );
            $userQueries[$username] = new Expression($stmt, null, ...$values);
        }

        if ($objectId) {
            $query->filter(\ipl\Stdlib\Filter::equal('id', hex2bin($objectId)));
        }

        $query->withColumns($userQueries);

        $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Cache-Control', 'no-store')
            ->setHeader('Content-Disposition', 'inline')
            ->sendResponse();

        ob_end_flush();

        echo '[';
        foreach ($query as $i => $result) {
            $users = [];
            foreach ($roles as $username => $_) {
                if ($result->$username) {
                    $users[] = $username;
                }
            }

            if ($i > 0) {
                echo PHP_EOL . ',';
            }

            echo json_encode([
                'id' => bin2hex($result->id),
                'users' => $users
            ]);
        }

        echo ']';
        exit;
    }
}
