<?php
/**
 * Created by PhpStorm.
 * User: nts
 * Date: 31.3.18.
 * Time: 17.03
 */

namespace Rackbeat\Utils;


class Model
{
    protected $entity;
    protected $primaryKey;
    protected $modelClass = self::class;
    protected $fillable   = [];

    /**
     * @var Request
     */
    protected $request;

    public function __construct( Request $request, $data = [] )
    {
        $this->request = $request;
        $data          = (array) $data;

        foreach ( $data as $key => $value ) {

            $customSetterMethod = 'set' . ucfirst( camel_case( $key ) ) . 'Attribute';

            if ( !method_exists( $this, $customSetterMethod ) ) {

                $this->setAttribute( $key, $value );

            } else {

                $this->setAttribute( $key, $this->{$customSetterMethod}( $value ) );
            }
        }
    }

    public function __toString()
    {
        return json_encode( $this->toArray() );
    }

    public function toArray()
    {
        $data       = [];
        $class      = new \ReflectionObject( $this );
        $properties = $class->getProperties( \ReflectionProperty::IS_PUBLIC );

        /** @var \ReflectionProperty $property */
        foreach ( $properties as $property ) {

            $data[ $property->getName() ] = $this->{$property->getName()};
        }

        return $data;
    }

    public function delete()
    {
        return $this->request->handleWithExceptions( function () {

            return $this->request->client->delete( "/{$this->entity}/{$this->{$this->primaryKey}}" );
        } );
    }

    public function update( $data = [] )
    {
        $data = array_merge( $this->toArray(), $data );

        return $this->request->handleWithExceptions( function () use ( $data ) {

            $response = $this->request->client->put( "/{$this->entity}/{$this->{$this->primaryKey}}", [
                'json' => $data,
            ] );

            $responseData = json_decode( $response->getBody()->getContents() );

            return new $this->modelClass( $this->request, $responseData );
        } );
    }

    protected function setAttribute( $attribute, $value )
    {
        $this->{$attribute} = $value;
    }
}