<?php
/**
 * Created by PhpStorm.
 * User: nts
 * Date: 31.3.18.
 * Time: 17.00
 */

namespace Rackbeat\Builders;

use Rackbeat\Utils\Model;
use Rackbeat\Utils\Request;


class Builder
{
    protected $entity;
    /** @var Model */
    protected $model;
    private   $request;

    public function __construct( Request $request )
    {
        $this->request = $request;
    }

    /**
     * @param array $filters
     *
     * @return \Illuminate\Support\Collection|Model[]
     */
    public function get( $filters = [] )
    {
        $urlFilters = '';

        if ( count( $filters ) > 0 ) {

            $urlFilters .= '?filter=';
            $i          = 1;

            foreach ( $filters as $filter ) {

                $urlFilters .= $filter[ 0 ] . $this->switchComparison( $filter[ 1 ] ) .
                               $this->escapeFilter( $filter[ 2 ] ); // todo fix arrays aswell ([1,2,3,...] string)

                if ( count( $filters ) > $i ) {

                    $urlFilters .= '$and:'; // todo allow $or: also
                }

                $i++;
            }
        }

        return $this->request->handleWithExceptions( function () use ( $urlFilters ) {

            $response     = $this->request->client->get( "{$this->entity}{$urlFilters}" );
            $responseData = json_decode( $response->getBody()->getContents() );
            $fetchedItems = collect( $responseData );
            $items        = collect( [] );

            foreach ( $fetchedItems->first() as $index => $item ) {


                /** @var Model $model */
                $model = new $this->model( $this->request, $item );

                $items->push( $model );


            }

            return $items;
        } );
    }

    public function find( $id )
    {
        return $this->request->handleWithExceptions( function () use ( $id ) {

            $response     = $this->request->client->get( "{$this->entity}/{$id}" );
            $responseData = collect( json_decode( $response->getBody()->getContents() ) );

            return new $this->model( $this->request, $responseData->first() );
        } );
    }

    public function create( $data )
    {
        return $this->request->handleWithExceptions( function () use ( $data ) {

            $response = $this->request->client->post( "{$this->entity}", [
                'json' => $data,
            ] );

            $responseData = collect( json_decode( $response->getBody()->getContents() ) );

            return new $this->model( $this->request, $responseData->first() );
        } );
    }

    private function escapeFilter( $variable )
    {
        $escapedStrings    = [
            "$",
            '(',
            ')',
            '*',
            '[',
            ']',
            ',',
        ];
        $urlencodedStrings = [
            '+',
            ' ',
        ];
        foreach ( $escapedStrings as $escapedString ) {

            $variable = str_replace( $escapedString, '$' . $escapedString, $variable );
        }
        foreach ( $urlencodedStrings as $urlencodedString ) {

            $variable = str_replace( $urlencodedString, urlencode( $urlencodedString ), $variable );
        }

        return $variable;
    }

    private function switchComparison( $comparison )
    {
        switch ( $comparison ) {
            case '=':
            case '==':
                $newComparison = '$eq:';
                break;
            case '!=':
                $newComparison = '$ne:';
                break;
            case '>':
                $newComparison = '$gt:';
                break;
            case '>=':
                $newComparison = '$gte:';
                break;
            case '<':
                $newComparison = '$lt:';
                break;
            case '<=':
                $newComparison = '$lte:';
                break;
            case 'like':
                $newComparison = '$like:';
                break;
            case 'in':
                $newComparison = '$in:';
                break;
            case '!in':
                $newComparison = '$nin:';
                break;
            default:
                $newComparison = "${$comparison}:";
                break;
        }

        return $newComparison;
    }
}