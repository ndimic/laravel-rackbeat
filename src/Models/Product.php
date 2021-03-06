<?php
/**
 * Created by PhpStorm.
 * User: nts
 * Date: 1.4.18.
 * Time: 00.02
 */

namespace Rackbeat\Models;


use Rackbeat\Utils\Model;

class Product extends Model
{
    public    $number;
    protected $entity     = 'products';
    protected $primaryKey = 'number';

    public function inventoryMatrix( $location_id = null )
    {
        return $this->request->handleWithExceptions( function () use ( $location_id ) {

            $filter = '';

            // We need to use location filter if user has provided any
            if ( !is_null( $location_id ) ) {

                $filter .= '?location_id=' . $location_id;
            }

            $response = $this->request->client->get( "{$this->entity}/{$this->{$this->primaryKey}}/variation-matrix" .
                                                     $filter );

            $html = $response->getBody()->getContents();

            return $html;
        } );

    }

    public function stock()
    {
        return $this->request->handleWithExceptions( function () {

            $response = $this->request->client->get( "{$this->entity}/{$this->{$this->primaryKey}}/stock_quantity" );

            $html = $response->getBody()->getContents();

            return $html;
        } );
    }
}