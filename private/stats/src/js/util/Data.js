'use strict'

module.exports = {
  invertMulti(collect) {
    return _.reduce(collect, (res, value, key) => {
      return _.reduce(value, (res2, value2, key2) => {
        res2[key2] = res2[key2] || {}
        res2[key2][key] = value2
        return res2
      }, res)
    }, {})
  },

  groupOthers(obj, n, {sum = null, sort = null}={}) {
    var obj = _.toPairs(obj)
    if (!sum) sum = (x, v) => {
      x = x || 0
      return x + v[1]
    }
    if (!sort) sort = (x) => {
      return -x[1]
    }

    var top = _.chain(obj)
      .sortBy(sort)
      .take(n)
      .value()

    top.push(_.reduce(_.difference(obj, top), (res, v, k) => {
      res[0] = res[0] || 'others'
      res[1] = sum(res[1], v)
      return res
    }, []))

    var ret = _.fromPairs(top)
    delete ret[undefined]

    return ret
  },
}
