<?php

namespace App\Admin\Controllers\Xhs;

use App\Models\XhsComment;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CommentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'XhsComment';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XhsComment());

        $grid->column('ID', __('ID'));
        $grid->column('X_ID', __('X ID'));
        $grid->column('PARENT_ID', __('PARENT ID'));
        $grid->column('NICKNAME', __('NICKNAME'));
        $grid->column('USER_ID', __('USER ID'));
        $grid->column('ISSUBCOMMENT', __('ISSUBCOMMENT'));
        $grid->column('CONTENT', __('CONTENT'));
        $grid->column('LIKES', __('LIKES'));
        $grid->column('ISLIKED', __('ISLIKED'));
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
        $show = new Show(XhsComment::findOrFail($id));

        $show->field('ID', __('ID'));
        $show->field('X_ID', __('X ID'));
        $show->field('PARENT_ID', __('PARENT ID'));
        $show->field('NICKNAME', __('NICKNAME'));
        $show->field('USER_ID', __('USER ID'));
        $show->field('ISSUBCOMMENT', __('ISSUBCOMMENT'));
        $show->field('CONTENT', __('CONTENT'));
        $show->field('LIKES', __('LIKES'));
        $show->field('ISLIKED', __('ISLIKED'));
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
        $form = new Form(new XhsComment());

        $form->number('ID', __('ID'));
        $form->text('X_ID', __('X ID'));
        $form->number('PARENT_ID', __('PARENT ID'));
        $form->text('NICKNAME', __('NICKNAME'));
        $form->text('USER_ID', __('USER ID'));
        $form->text('ISSUBCOMMENT', __('ISSUBCOMMENT'));
        $form->textarea('CONTENT', __('CONTENT'));
        $form->number('LIKES', __('LIKES'));
        $form->text('ISLIKED', __('ISLIKED'));
        $form->text('TIME', __('TIME'));
        $form->text('CREATED_AT', __('CREATED AT'));
        $form->text('UPDATED_AT', __('UPDATED AT'));

        return $form;
    }
}
