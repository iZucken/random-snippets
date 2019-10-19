<?php

$examples = [
    "^$" => [ "", "zzz" ],
    "^abc" => [ "abc", "abczzz" ],
    "abc$" => [],
    "^/abc/(dfg|bca)" => [ "/abc/dfg", "/abc/bca", "/abc/bca/zzz" ],
    "^/abc/[(dfg)(bca)]+" => [ "/abc/dfg", "/abc/bcadfg", "/abc/bca/zzz" ],
    "^/abc/.+" => [],
    "^/abc/.*" => [],
    "^/abc/\.\*" => [ "/abc/.*", "/abc/zzz" ],
    "^/abc/[^/]+" => [ "/abc/zzz", "/abc/zzz/", "/abc//zzz" ],
    "^.*ab.*.*c.*$" => [ "/abc/zzz", "/abc/zzz/", "/abc//zzz" ],
];

class regexCalculator
{
    private $dictionaryLimit = 255;
    private $inputLimit = 65536;
    private $tokens = [
        '^',
        '$',
        '[',
        ']',
        '(',
        ')',
        '{',
        '}',
        '|',
        '.',
        '?',
        '+',
        '*',
    ];
    private $compiledMatcher = null;

    function tokenMatcher () {
        return $this->compiledMatcher ?? $this->compiledMatcher = ( "/([(\\" . join( ')(\\', $this->tokens ) . ")])/" );
    }

    function tokenize ( $regex ) {
        if ( false === preg_match( "~$regex~", null ) ) {
            throw new Exception( "Invalid pattern" );
        }
        $regex = $this->dilate( $regex );
        $split = preg_split( $this->tokenMatcher(), $regex, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
        return $split;
    }

    function dilateTokens ( $tokens ) {
        foreach ( $tokens as &$token ) {
            if ( !in_array( $token, $this->tokens ) ) {
                $token = "A";
            }
        }
        unset( $token );
        return $tokens;
    }

    private $dilationHeuristics = [
        "/\^\.\*/" => ".*",
        "/\.\*\.\*/" => ".*",
        "/\.\*\\$/" => ".*",
        "/([^\$(\.\*)])$/" => "$1.*",
        "/^([^\^(\.\*)])/" => ".*$1",
    ];

    function dilate ( $regex ) {
        return preg_replace( array_keys( $this->dilationHeuristics ), array_values( $this->dilationHeuristics ), $regex );
    }
}

$rc = new regexCalculator();

function line ( $line ) {
    echo $line, "\n";
}

foreach ( $examples as $example => $samples ) {
    $tokens = $rc->tokenize( $example );
    line( '<' . join( "> <", $tokens ) . '>' );
    $dilated = $rc->dilateTokens( $tokens );
    line( '<' . join( "> <", $dilated ) . '>' );
    if ( !empty( $samples ) ) {
        foreach ( $samples as $sample ) {
            $matches = preg_match( "~$example~", $sample );
            if ( $matches ) {
                line( "\t=> $sample" );
            } else {
                line( "\t!= $sample" );
            }
        }
    }
}

/*



D : dictionary size
L : input limit
x & y = x ^ (0..y)
M = D & L : practical maximum of possible combinations
a..z : multiple combinations
A..Z => 1 : single combination

### M-N combinations based (specificity) ###

A -> M - A
a -> M / a



### N combinations based ###

~ : transitive chain of combinations

expression chaining:

0~x = x
x~A~A~x = x~A~x
x~M~M~x = x~M~x
x~a~a~x = x~(a*a)~x
x~A~a~x = x~a~x
x~A~M~x = x~(M/a)~x ??
x~a~M~x = x~(M-a)~x ??

M*A = M*a = M+a = M+a = M

transitivity example:

0AaBbcM -> 0A~aB~bc~M
-> A~a~(b*c)~M, (b*c) = d
-> AadM -> Aa~dM
-> a~(M-d), (M-d) = e
-> ae
-> a(M-(b*c)) = (M - a*b*c)

McbBaA0 -> Mc~bB~aA~0
-> (M-c)~b~a~0, (M-c) = d
-> dba0 -> db~a0
-> (d*b)~a, (d*b) = e
-> ea
-> e*a -> (d*b)*a -> d*b*a -> (M-c)*b*a -> (M*b*a - c*b*a) -> (M - a*b*c)

symbols:

  -> inf : absence of opening / closing terminator is equal to a followup of inf
. -> D
^ -> 0
$ -> 0
x? -> 1 + x
x* -> x & L
x+ -> xx* -> x + x & L
^a.$ -> 1 -> D
^.*$ -> .* -> M
[xy] -> x + y
x|y -> x + y
^(a|.)$ -> ( 1 + D ) ?!?!?!
^abc..* -> 0AaM -> aM -> M-D
^(abc|.).* -> A = abc, a = . = D, b = (A|a), .* = M, ^ = 0 -> 0bM -> M-b = M-(A+a) = M-(A+D)

*/



/*

Additional quest:
    - From a pool of regex, find those that are intersecting?
    - Generate matching input for regex?
    - Unroll specific syntaxes, such as non-greedy, look-around, etc.

Special case context:
Regexes are for URI
Means characters dictionary size is defined by rfc:

     reserved    = gen-delims / sub-delims
      gen-delims  = ":" / "/" / "?" / "#" / "[" / "]" / "@"
      sub-delims  = "!" / "$" / "&" / "'" / "(" / ")" / "*" / "+" / "," / ";" / "="
     unreserved  = ALPHA / DIGIT / "-" / "." / "_" / "~"

… and de-facto maximum matched string size is expected to not exceed 2000 characters


Broadness or narrowness?

Task is to find the most broad or narrow
amongst many regular expressions
matching a given sequence.

We need some heuristic for “ * ” or “ + ”

Nd - dictionary size
Ns - possible sequence size (heuristic)
{ character sequence } = A
. = Nd
. * = INF
X * = { X times Ns }

$ => A => ^ ~> ( min broadness, max specificity )
INF ~> ( max broadness, min specificity )

For the sake of clarity, converted expression omits sequence start and end marks in favor of explicit infinity marks

Expansions:
A + = ( A => A * )
( $ => A) = ( A => INF )
( A => ^ ) = ( INF => A )
A = ( INF => A => INF )

Collapses:
A = 1
( A => B ) = A
( A * => A * ) = A *
( A | … ) = A + ...

After operations resulting set is further converted into numeric sequence:

[0-9]+(asd|cds|qwe)[abc]d^
.* ~> (A..10) ~> (A..10) * ~> (A|B|C) ~> (A|B|C) ~> A ~> ^
INF ~> 10 ~> 10 * Ns ~> 3 ~> 3 ~> 1
INF * 10 * 10 * Ns * 3 * 3 * 1
INF * 900 * Ns



Examples of broadness comparison:

$ A ( B | C ) D ^ == $ A ( B | C ) ^

$ A . ^  <  $ A . B ^

$ A . B ^  <  $ A . * B . * C ^

$ A . * B . * C ^  <  $ A . * B . *

$ A ( B | C ) D . *  <  $ A ( B | C ) . *

$ A . B . *  <  $ A . *


*/