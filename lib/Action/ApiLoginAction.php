<?php
namespace DotLogics\Action;

use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Encryption\Factory;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use Emarref\Jwt\Claim\Audience;
use Emarref\Jwt\Claim\Expiration;
use Emarref\Jwt\Claim\IssuedAt;
use Emarref\Jwt\Claim\Issuer;
use Emarref\Jwt\Claim\JwtId;
use Emarref\Jwt\Claim\PublicClaim;
use Emarref\Jwt\Claim\PrivateClaim;
use Emarref\Jwt\Verification\Context;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use DotLogics\DB\UserDB;
use DateTime;
use Slim\Views\Twig;

class ApiLoginAction
{
    const TOKEN_ISSUER = 'dotlogicshungarykft';
    const TOKEN_ID = 'LkPx18WhszTr419p';
    const TOKEN_AUDIENCE = 'timesheetusers';

    /** @var  Twig */
    private $_view;

    /** @var  PDO */
    protected $_db;

    protected $_log;

    /** @var  UserDB */
    private $_loggedUser;

    /**
     * ApiLoginAction constructor.
     * @param $db
     */
    public function __construct($db, $log)
    {
        $this->_log = $log;
        $this->_db = $db;
        $this->_loggedUser = NULL;
    }

    public function index(Request $request, Response $response, $args)
    {
        $this->_view->render($response, 'admin/index.html', array());

        return $response;
    }

    /*
     * Handle Login
     *
     * input: email and password
     * output: JWT token
     *
     * */
    public function login(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents('php://input'));
        $email = isset($params->user) ? $params->user : $request->getParam('user');
        $password = isset($params->password) ? $params->password : $request->getParam('password');
        $valid = FALSE;

        if(!$this->getLoggedUser()){
            $user = new UserDB($this->_db, $this->_log);
            $currUser = $user->getUserByEmail($email);
            $valid = $currUser && $currUser->getId() && $user->isValidUserPassword($password);
        }

        if($valid && !$this->getLoggedUser()) {
            $this->setLoggedUser($currUser);
        }

        if($this->getLoggedUser() instanceof UserDB)
        {
            $loggedUser  = $this->getLoggedUser();

            $token = new Token();
            $token->addClaim(new Audience([self::TOKEN_AUDIENCE]));
            $token->addClaim(new Expiration(new DateTime('180 minutes')));
            $token->addClaim(new IssuedAt(new DateTime('now')));
            $token->addClaim(new Issuer(self::TOKEN_ISSUER));
            $token->addClaim(new JwtId(self::TOKEN_ID));
            $token->addClaim(new PublicClaim('userid', $loggedUser->getId()));

            $jwt = new Jwt();
            //$algorithm = new Hs256(base64_encode(openssl_random_pseudo_bytes(32)));
            $algorithm = new Hs256(getenv('JWT_KEY'));
            $encryption = Factory::create($algorithm);
            $serializedToken = $jwt->serialize($token, $encryption);

            return $response->withStatus(200)
                ->withHeader('Content-Type','application/json')
                ->write(json_encode(array('token_id' => $serializedToken)));
        }

        return $response->withStatus(401);
    }

    /**
     * @param $token
     * @return int|mixed
     */
    public static function isValidToken($token)
    {
        $userId = 0;
        $jwt = new Jwt();
        $token = $jwt->deserialize($token);
        $algorithm = new Hs256(getenv('JWT_KEY'));
        $encryption = Factory::create($algorithm);
        $context = new Context($encryption);
        $context->setAudience(ApiLoginAction::TOKEN_AUDIENCE);
        $context->setIssuer(ApiLoginAction::TOKEN_ISSUER);
        $jwt->verify($token, $context);

        foreach($token->getPayload()->getClaims()->getIterator() as $item)
        {
            if($item instanceof PrivateClaim && $item->getName() == 'userid')
            {
                $userId = $item->getValue();
            }
        }

        return $userId;
    }

    /**
     * @param UserDB $user
     */
    public function setLoggedUser(UserDB $user)
    {
        $this->_loggedUser = $user;
    }

    /**
     * @return UserDB|null
     */
    public function getLoggedUser()
    {
        return $this->_loggedUser;
    }
}
?>