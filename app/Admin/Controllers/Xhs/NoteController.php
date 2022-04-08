<?php

namespace App\Admin\Controllers\Xhs;

use App\Models\XhsNote;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class NoteController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'XhsNote';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XhsNote());

        $grid->column('ID', __('ID'));
        $grid->column('X_ID', __('X ID'));
        $grid->column('TITLE', __('TITLE'));
        $grid->column('DESC', __('DESC'));
        $grid->column('ISLIKED', __('ISLIKED'));
        $grid->column('TYPE', __('TYPE'));
        $grid->column('TIME', __('TIME'));
        $grid->column('CREATED_AT', __('CREATED AT'));
        $grid->column('UPDATED_AT', __('UPDATED AT'));

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(XhsNote::findOrFail($id));

        $show->field('ID', __('ID'));
        $show->field('X_ID', __('X ID'));
        $show->field('TITLE', __('TITLE'));
        $show->field('DESC', __('DESC'));
        $show->field('ISLIKED', __('ISLIKED'));
        $show->field('TYPE', __('TYPE'));
        $show->field('TIME', __('TIME'));
        $show->field('CREATED_AT', __('CREATED AT'));
        $show->field('UPDATED_AT', __('UPDATED AT'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new XhsNote());

        $form->number('ID', __('ID'));
        $form->text('X_ID', __('X ID'));
        $form->text('TITLE', __('TITLE'));
        $form->textarea('DESC', __('DESC'));
        $form->text('ISLIKED', __('ISLIKED'));
        $form->text('TYPE', __('TYPE'));
        $form->text('TIME', __('TIME'));
        $form->text('CREATED_AT', __('CREATED AT'));
        $form->text('UPDATED_AT', __('UPDATED AT'));

        return $form;
    }
}
