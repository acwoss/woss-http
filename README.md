# woss/http [![Build Status](https://travis-ci.org/acwoss/woss-http.svg?branch=master)](https://travis-ci.org/acwoss/woss-http)

> Não, não é mais um framework PHP.

O pacote `Woss\Http` é um compilado de classes que visam facilitar o trabalho sobre o protocolo HTTP na linguagem PHP, deixando de forma transparente e explícita o que acontece na aplicação e permitindo testes unitários mais simples.

São previstos três *namespaces* dentro do pacote, sendo eles:

- `Woss\Http\Message`, com classes para se trabalhar com mensagens HTTP;
- `Woss\Http\Server`, com classes que facilitam o desenvolvimento da aplicação;
- `Woss\Http\Client`, com classes para requisições externas;

## Instalação

A maneira mais simples é fazer a instalação via Composer:

```text
$ composer install woss/http
```

## Aplicação

A aplicação construída com `Woss\Http` seguirá o conceito de *middlewares*, onde há a distinção e separação clara das responsabilidades entre cada componente da aplicação. A requisição HTTP se propagará através de uma arquitetura *pipe* até o momento que a resposta HTTP for gerada.

A arquitetura *pipe* suporta três entidades básicas:

1. Funções anônimas `function (Request $request, $next): Response)`;
2. Classes *handlers* que implementam o método `handle(Request $request): Response`;
3. Classes *middlewares* que implementam o método `process(Request $request, $next): Response`; 

### Funções anônimas

A entidade baseada na função anônima possui dois parâmetros: `$request`, que representará a requisição HTTP de entrada, e `$next`, que representará a próxima entidade da *pipe*. O retorno deve ser sempre uma instância de `Response`.

```php
use Woss\Http\Message\{Request, Response};

function (Request $request, $next): Response
{
    log('debug', (string) $request);
    
    $response = $next($request);
    
    log('debug', (string) $response);
    
    return $response;
}
```

### Classes *Handlers*

As classes *handlers* são características por implementarem o método `handle`, que recebe apenas um parâmetro, `$request`, que representará a requisição HTTP de entrada, e gerando uma instância de `Response`. Diferente das funções anônimas e das classes *middlewares*, uma classe *handler* não possui autonomia para propagar a requisição pela *pipe*, então obrigatoriamente ela deverá gerar uma resposta HTTP. Assim, é comum vermos as classes *handlers* apenas no final da *pipe*.

```php
use Woss\Http\Message\{Request, Response, Stream};

class ListAllUsers
{
    public function __construct($users)
    {
        $this->users = $users;
    }
    
    public function handle(Request $request): Response
    {
        $users = $this->users->getAll();
        $content = json_encode($users);
        
        $body = new Stream('php://memory', 'w');
        
        $body->write($content);
        
        $response = new Response($body, 200, [
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($content),
        ]);
        
        return $response;
    }
}
```

### Classes *Middlewares*

As classes *middlewares* são características por implementarem o método `process`, que recebe dois parâmetros, `$request`, que representa a requisição HTTP de entrada, e `$next`, que representa a próxima entidade da arquitetura *pipe*, retornando sempre uma instância de `Response`. Por possuir o parâmetro `$next`, uma classe *middleware*, assim como a função anônima, possui autoridade para propagar a requisição pelo resto da *pipe*, o que tira dela a obrigação de gerar uma resposta HTTP. Com isso, as classes *middlewares* são mais comuns nas responsabilidades de modificarem a requisição HTTP de entrada e/ou a resposta HTTP produzida por outras entidades.

```php
use Woss\Http\Message\{Request, Response};

class Cache
{
    public function __construct($cache)
    {
        $this->cache = $cache;
    }
    
    public function process(Request $request, $next): Response
    {
        // Se houver cache da resposta, retorne-a
        if ($this->cache->exists($request)) {
            return $this->cache->get($request);
        }
        
        // Caso contrário, gere a resposta HTTP e a armazene no cache
        $response = $next($request);
        
        $this->cache->create($request, $response);
        
        return $response;
    }
}
```

## Arquitetura *Pipe*

A arquitetura *pipe* é implementada a partir da classe `Woss\Http\Server\Pipeline`, que implementa o método `handle(Request $request): Response`. Todas as entidades da *pipe* são definitas por um objeto iterável passado ao construtor da classe.

```php
use Woss\Http\Message\{ServerRequest, Request, Response};
use Woss\Http\Server\Pipeline;

$pipe = new Pipeline([
    function (Request $request, $next): Response
    {
        log('debug', (string) $request);
        
        $response = $next($request);
        
        log('debug', (string) $response);
        
        return $response;
    },
    new Cache($services->get('cache')),
    new ListAllUsers($services->get('users'))
]);

$request = ServerRequest::fromGlobals();
$response = $pipe->handle($request);

echo (string) $response;
```