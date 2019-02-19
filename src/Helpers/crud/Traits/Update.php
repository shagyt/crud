<?php

namespace Shagyt\lvcrud\Helpers\crud\Traits;

trait Update
{
    /*
    |--------------------------------------------------------------------------
    |                                   UPDATE
    |--------------------------------------------------------------------------
    */

    /**
     * Update a row in the database.
     *
     * @param  [Int] The entity's id
     * @param  [Request] All inputs to be updated.
     *
     * @return [Eloquent Collection]
     */
    public function update($id, $data)
    {
        $data = $this->decodeJsonCastedAttributes($data);
        // $data = $this->compactFakeFields($data, 'update', $id);

        $item = $this->model->findOrFail($id);

        // $this->syncPivot($item, $data, 'update');
        // echo "<pre>";
        // echo json_encode($data, JSON_PRETTY_PRINT);
        // echo "<br><br><br><br>";
        // echo json_encode(collect($data)->only($this->column_names)->toArray(), JSON_PRETTY_PRINT);
        // echo "</pre>";
        // return ;
        
        // ommit the n-n relationships when updating the eloquent item
        // $nn_relationships = array_pluck($this->getRelationFieldsWithPivot('update'), 'name');
        // $data = array_except($data, $nn_relationships);
        $updated = $item->update(collect($data)->only($this->column_names)->toArray());

        return $item;
    }

    /**
     * Get all fields needed for the EDIT ENTRY form.
     *
     * @param  [integer] The id of the entry that is being edited.
     * @param int $id
     *
     * @return [array] The fields with attributes, fake attributes and values.
     */
    public function getUpdateFields($id)
    {
        $fields = $this->update_fields;
        $entry = $this->getEntry($id);
        
        foreach ($fields as $k => $field) {
            // set the value
            if (! isset($fields[$k]['value'])) {
                if (isset($field['subfields'])) {
                    $fields[$k]['value'] = [];
                    foreach ($field['subfields'] as $key => $subfield) {
                        $fields[$k]['value'][] = $entry->{$subfield['name']};
                    }
                } else {
                    if(isset($field['name'])) {
                        $fields[$k]['value'] = $entry->{$field['name']};
                    } else {
                        $fields[$k]['value'] = $entry->{$field['name']};
                    }
                }
            }
        }

        // always have a hidden input for the entry id
        if (! array_key_exists('id', $fields)) {
            $fields['id'] = [
                'name'  => $entry->getKeyName(),
                'value' => $entry->getKey(),
                'type'  => 'hidden',
            ];
        }

        return $fields;
    }
}
